<?php

namespace Tests\Feature\Integration;

use App\Jobs\SendMessageJob;
use App\Models\Device;
use App\Models\MessageLog;
use App\Models\Tenant;
use App\Services\Contracts\GatewayClientInterface;
use App\Services\DeviceService;
use App\Services\WahaGatewayClient;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GatewayBindingTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * DI container resolves GatewayClientInterface to WahaGatewayClient.
     *
     * Validates: Requirements 1.7
     */
    #[Test]
    public function container_resolves_gateway_client_interface_to_waha_gateway_client(): void
    {
        $resolved = $this->app->make(GatewayClientInterface::class);

        $this->assertInstanceOf(WahaGatewayClient::class, $resolved);
    }

    /**
     * DeviceService can be resolved from the container without binding errors.
     *
     * Validates: Requirements 1.7, 1.8
     */
    #[Test]
    public function container_resolves_device_service(): void
    {
        $resolved = $this->app->make(DeviceService::class);

        $this->assertInstanceOf(DeviceService::class, $resolved);
    }

    /**
     * SendMessageJob can be instantiated and its handle() method injection resolves
     * GatewayClientInterface from the container without binding errors.
     *
     * Validates: Requirements 1.7, 1.8
     */
    #[Test]
    public function container_resolves_send_message_job(): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->for($tenant)->create(['status' => 'connected']);
        $messageLog = MessageLog::factory()->for($tenant)->for($device)->create();

        // Verify the job can be constructed (no binding errors for its dependencies)
        $job = new SendMessageJob($messageLog);
        $this->assertInstanceOf(SendMessageJob::class, $job);

        // Verify the container can resolve GatewayClientInterface that handle() will inject
        $gatewayClient = $this->app->make(GatewayClientInterface::class);
        $this->assertInstanceOf(WahaGatewayClient::class, $gatewayClient);
    }
}
