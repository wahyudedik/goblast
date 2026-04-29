<?php

namespace App\Console\Commands;

use App\Exceptions\GatewayException;
use App\Models\Alert;
use App\Models\Device;
use App\Services\Contracts\BaileysGatewayClientInterface;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('device:health-check')]
#[Description('Check health status of all connected devices')]
class CheckDeviceHealth extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(BaileysGatewayClientInterface $client): int
    {
        $this->info('Starting device health check...');

        // Query all connected devices
        $devices = Device::with('tenant')
            ->where('status', 'connected')
            ->get();

        if ($devices->isEmpty()) {
            $this->info('No connected devices found.');

            return self::SUCCESS;
        }

        $this->info("Checking {$devices->count()} connected device(s)...");

        $healthyCount = 0;
        $disconnectedCount = 0;
        $errorCount = 0;

        foreach ($devices as $device) {
            try {
                // Call BaileysGatewayClient::getConnectionStatus
                $status = $client->getConnectionStatus($device->gateway_device_id);

                if ($status === 'connected') {
                    // Update last_seen_at
                    $device->update(['last_seen_at' => now()]);
                    $healthyCount++;
                    $this->info("✓ Device '{$device->name}' (ID: {$device->id}): Healthy");
                } elseif ($status === 'disconnected') {
                    // Update device status to disconnected
                    $device->update([
                        'status' => 'disconnected',
                        'last_seen_at' => now(),
                    ]);
                    $disconnectedCount++;
                    $this->warn("✗ Device '{$device->name}' (ID: {$device->id}): Disconnected");

                    // Create alert
                    Alert::create([
                        'tenant_id' => $device->tenant_id,
                        'type' => 'device.disconnected',
                        'severity' => 'warning',
                        'message' => "Device '{$device->name}' has been disconnected",
                        'context' => [
                            'device_id' => $device->id,
                            'device_name' => $device->name,
                            'gateway_device_id' => $device->gateway_device_id,
                        ],
                        'status' => 'active',
                    ]);
                } else {
                    // Update device status to error
                    $device->update([
                        'status' => 'error',
                        'last_seen_at' => now(),
                    ]);
                    $errorCount++;
                    $this->error("✗ Device '{$device->name}' (ID: {$device->id}): Error");

                    // Create alert
                    Alert::create([
                        'tenant_id' => $device->tenant_id,
                        'type' => 'device.error',
                        'severity' => 'error',
                        'message' => "Device '{$device->name}' encountered an error",
                        'context' => [
                            'device_id' => $device->id,
                            'device_name' => $device->name,
                            'gateway_device_id' => $device->gateway_device_id,
                            'status' => $status,
                        ],
                        'status' => 'active',
                    ]);
                }
            } catch (GatewayException $e) {
                // Update device status to error
                $device->update([
                    'status' => 'error',
                    'last_seen_at' => now(),
                ]);
                $errorCount++;
                $this->error("✗ Device '{$device->name}' (ID: {$device->id}): Gateway error - {$e->getMessage()}");

                // Create alert
                Alert::create([
                    'tenant_id' => $device->tenant_id,
                    'type' => 'device.error',
                    'severity' => 'error',
                    'message' => "Device '{$device->name}' health check failed: {$e->getMessage()}",
                    'context' => [
                        'device_id' => $device->id,
                        'device_name' => $device->name,
                        'gateway_device_id' => $device->gateway_device_id,
                        'error' => $e->getMessage(),
                    ],
                    'status' => 'active',
                ]);

                Log::error('Device health check failed', [
                    'device_id' => $device->id,
                    'gateway_device_id' => $device->gateway_device_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $summary = [
            'total_devices' => $devices->count(),
            'healthy' => $healthyCount,
            'disconnected' => $disconnectedCount,
            'error' => $errorCount,
            'timestamp' => now()->toDateTimeString(),
        ];

        Log::info('Device health check completed', $summary);

        $this->newLine();
        $this->info('Device health check completed:');
        $this->table(
            ['Status', 'Count'],
            [
                ['Total Devices', $summary['total_devices']],
                ['Healthy', $summary['healthy']],
                ['Disconnected', $summary['disconnected']],
                ['Error', $summary['error']],
            ]
        );

        return self::SUCCESS;
    }
}
