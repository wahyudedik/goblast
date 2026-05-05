<?php

namespace App\Services;

use App\Exceptions\DeviceLimitExceededException;
use App\Exceptions\GatewayException;
use App\Models\Device;
use App\Models\Tenant;
use App\Services\Contracts\DeviceServiceInterface;
use App\Services\Contracts\GatewayClientInterface;
use Illuminate\Support\Str;

class DeviceService implements DeviceServiceInterface
{
    public function __construct(
        protected GatewayClientInterface $gatewayClient,
    ) {}

    /**
     * Request a new device connection and generate QR code.
     *
     * @throws DeviceLimitExceededException
     * @throws GatewayException
     */
    public function requestConnection(Tenant $tenant, string $deviceName): Device
    {
        // Validate device limit based on subscription plan
        if (! $this->canAddDevice($tenant)) {
            $activeSubscription = $tenant->subscriptions()
                ->where('status', 'active')
                ->first();

            $maxDevices = $activeSubscription?->plan->max_devices ?? 1;
            $currentCount = $tenant->devices()->count();

            throw new DeviceLimitExceededException($currentCount, $maxDevices);
        }

        // Generate unique gateway device ID
        $gatewayDeviceId = Str::uuid()->toString();

        // Request QR code from Baileys Gateway
        try {
            $qrCode = $this->gatewayClient->getQrCode($gatewayDeviceId);
        } catch (GatewayException $e) {
            throw new GatewayException('Failed to request QR code from gateway', $e->gatewayError);
        }

        // Create device record with pending status
        $device = Device::create([
            'tenant_id' => $tenant->id,
            'name' => $deviceName,
            'gateway_device_id' => $gatewayDeviceId,
            'status' => 'pending',
        ]);

        return $device;
    }

    /**
     * Confirm device connection after QR code is scanned.
     *
     * @throws GatewayException
     */
    public function confirmConnection(string $deviceId, string $sessionData): void
    {
        $device = Device::findOrFail($deviceId);

        try {
            // Verify connection status with gateway
            $status = $this->gatewayClient->getConnectionStatus($device->gateway_device_id);

            if ($status !== 'connected') {
                throw new GatewayException('Device connection verification failed', "Status: {$status}");
            }

            // Update device with connection details (session_data will be auto-encrypted by model cast)
            $device->update([
                'status' => 'connected',
                'session_data' => $sessionData,
                'last_seen_at' => now(),
            ]);
        } catch (GatewayException $e) {
            $device->update(['status' => 'error']);
            throw $e;
        }
    }

    /**
     * Check the current connection status of a device.
     *
     * @return string One of: 'connected', 'disconnected', 'error'
     *
     * @throws GatewayException
     */
    public function checkConnectionStatus(Device $device): string
    {
        try {
            $status = $this->gatewayClient->getConnectionStatus($device->gateway_device_id);

            // Update device status if changed
            if ($status !== $device->status) {
                $device->update([
                    'status' => $status,
                    'last_seen_at' => $status === 'connected' ? now() : $device->last_seen_at,
                ]);
            } elseif ($status === 'connected') {
                // Update last_seen_at for connected devices
                $device->update(['last_seen_at' => now()]);
            }

            return $status;
        } catch (GatewayException $e) {
            // Mark device as error on gateway communication failure
            $device->update(['status' => 'error']);
            throw $e;
        }
    }

    /**
     * Disconnect a device and cleanup session data.
     *
     * @throws GatewayException
     */
    public function disconnect(Device $device): void
    {
        try {
            // Notify gateway to disconnect device
            $this->gatewayClient->disconnectDevice($device->gateway_device_id);
        } catch (GatewayException $e) {
            // Log error but continue with cleanup
            \Log::warning('Gateway disconnect failed for device', [
                'device_id' => $device->id,
                'gateway_device_id' => $device->gateway_device_id,
                'error' => $e->getMessage(),
            ]);
        }

        // Cleanup session data and update status
        $device->update([
            'status' => 'disconnected',
            'session_data' => null,
            'phone_number' => null,
        ]);
    }

    /**
     * Check if tenant can add another device based on subscription plan.
     */
    public function canAddDevice(Tenant $tenant): bool
    {
        // Get active subscription
        $activeSubscription = $tenant->subscriptions()
            ->where('status', 'active')
            ->first();

        if (! $activeSubscription) {
            return false;
        }

        // Check if plan allows multi-device
        if (! $activeSubscription->plan->has_multi_device) {
            // Single device plan - check if already has a connected device
            $connectedDevices = $tenant->devices()
                ->whereIn('status', ['connected', 'pending'])
                ->count();

            return $connectedDevices === 0;
        }

        // Multi-device plan - check against max_devices limit
        $connectedDevices = $tenant->devices()
            ->whereIn('status', ['connected', 'pending'])
            ->count();

        return $connectedDevices < $activeSubscription->plan->max_devices;
    }

    /**
     * Get QR code for device connection.
     *
     * @throws GatewayException
     */
    public function getQrCode(Device $device): string
    {
        try {
            return $this->gatewayClient->getQrCode($device->gateway_device_id);
        } catch (GatewayException $e) {
            throw new GatewayException('Failed to get QR code from gateway', $e->gatewayError);
        }
    }
}
