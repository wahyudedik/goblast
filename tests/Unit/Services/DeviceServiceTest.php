<?php

namespace Tests\Unit\Services;

use App\Exceptions\DeviceLimitExceededException;
use App\Exceptions\GatewayException;
use App\Models\Device;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\Contracts\BaileysGatewayClientInterface;
use App\Services\DeviceService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DeviceServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    private DeviceService $deviceService;

    private BaileysGatewayClientInterface $gatewayClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gatewayClient = $this->createMock(BaileysGatewayClientInterface::class);
        $this->deviceService = new DeviceService($this->gatewayClient);
    }

    public function test_can_request_connection_with_valid_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['max_devices' => 1, 'has_multi_device' => false]);
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        $this->gatewayClient
            ->expects($this->once())
            ->method('getQrCode')
            ->willReturn('qr_code_data');

        $device = $this->deviceService->requestConnection($tenant, 'Test Device');

        $this->assertInstanceOf(Device::class, $device);
        $this->assertEquals($tenant->id, $device->tenant_id);
        $this->assertEquals('Test Device', $device->name);
        $this->assertEquals('pending', $device->status);
        $this->assertNotNull($device->gateway_device_id);
    }

    public function test_request_connection_throws_exception_when_device_limit_exceeded(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['max_devices' => 1, 'has_multi_device' => false]);
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        // Create an existing connected device
        Device::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'connected',
        ]);

        $this->expectException(DeviceLimitExceededException::class);
        $this->expectExceptionMessage('Device limit exceeded');

        $this->deviceService->requestConnection($tenant, 'Another Device');
    }

    public function test_request_connection_throws_exception_when_no_active_subscription(): void
    {
        $tenant = Tenant::factory()->create();

        $this->expectException(DeviceLimitExceededException::class);

        $this->deviceService->requestConnection($tenant, 'Test Device');
    }

    public function test_request_connection_throws_gateway_exception_on_qr_code_failure(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['max_devices' => 1]);
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        $this->gatewayClient
            ->expects($this->once())
            ->method('getQrCode')
            ->willThrowException(new GatewayException('QR code generation failed'));

        $this->expectException(GatewayException::class);

        $this->deviceService->requestConnection($tenant, 'Test Device');
    }

    public function test_can_confirm_connection(): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'pending',
        ]);
        $sessionData = 'encrypted_session_data';

        $this->gatewayClient
            ->expects($this->once())
            ->method('getConnectionStatus')
            ->with($device->gateway_device_id)
            ->willReturn('connected');

        $this->deviceService->confirmConnection($device->id, $sessionData);

        $device->refresh();
        $this->assertEquals('connected', $device->status);
        $this->assertNotNull($device->session_data);
        // Session data is encrypted by model cast, so it should match when accessed
        $this->assertEquals($sessionData, $device->session_data);
    }

    public function test_confirm_connection_throws_exception_on_verification_failure(): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'pending',
        ]);

        $this->gatewayClient
            ->expects($this->once())
            ->method('getConnectionStatus')
            ->willReturn('disconnected');

        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('Device connection verification failed');

        $this->deviceService->confirmConnection($device->id, 'session_data');

        $device->refresh();
        $this->assertEquals('error', $device->status);
    }

    public function test_can_check_connection_status(): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'connected',
        ]);

        $this->gatewayClient
            ->expects($this->once())
            ->method('getConnectionStatus')
            ->with($device->gateway_device_id)
            ->willReturn('connected');

        $status = $this->deviceService->checkConnectionStatus($device);

        $this->assertEquals('connected', $status);
    }

    public function test_check_connection_status_updates_device_when_disconnected(): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'connected',
        ]);

        $this->gatewayClient
            ->expects($this->once())
            ->method('getConnectionStatus')
            ->willReturn('disconnected');

        $status = $this->deviceService->checkConnectionStatus($device);

        $this->assertEquals('disconnected', $status);
        $device->refresh();
        $this->assertEquals('disconnected', $device->status);
    }

    public function test_check_connection_status_marks_device_as_error_on_gateway_failure(): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'connected',
        ]);

        $this->gatewayClient
            ->expects($this->once())
            ->method('getConnectionStatus')
            ->willThrowException(new GatewayException('Gateway unreachable'));

        $this->expectException(GatewayException::class);

        $this->deviceService->checkConnectionStatus($device);

        $device->refresh();
        $this->assertEquals('error', $device->status);
    }

    public function test_can_disconnect_device(): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'connected',
            'session_data' => 'encrypted_data',
            'phone_number' => '6281234567890',
        ]);

        $this->gatewayClient
            ->expects($this->once())
            ->method('disconnectDevice')
            ->with($device->gateway_device_id);

        $this->deviceService->disconnect($device);

        $device->refresh();
        $this->assertEquals('disconnected', $device->status);
        $this->assertNull($device->session_data);
        $this->assertNull($device->phone_number);
    }

    public function test_disconnect_device_continues_on_gateway_error(): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'connected',
            'session_data' => 'encrypted_data',
        ]);

        $this->gatewayClient
            ->expects($this->once())
            ->method('disconnectDevice')
            ->willThrowException(new GatewayException('Gateway error'));

        // Should not throw exception
        $this->deviceService->disconnect($device);

        $device->refresh();
        $this->assertEquals('disconnected', $device->status);
        $this->assertNull($device->session_data);
    }

    public function test_can_add_device_with_single_device_plan(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['max_devices' => 1, 'has_multi_device' => false]);
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        $this->assertTrue($this->deviceService->canAddDevice($tenant));
    }

    public function test_cannot_add_device_when_single_device_plan_has_connected_device(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['max_devices' => 1, 'has_multi_device' => false]);
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        Device::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'connected',
        ]);

        $this->assertFalse($this->deviceService->canAddDevice($tenant));
    }

    public function test_can_add_device_with_multi_device_plan(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['max_devices' => 5, 'has_multi_device' => true]);
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        Device::factory()->count(3)->create([
            'tenant_id' => $tenant->id,
            'status' => 'connected',
        ]);

        $this->assertTrue($this->deviceService->canAddDevice($tenant));
    }

    public function test_cannot_add_device_when_multi_device_limit_exceeded(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['max_devices' => 2, 'has_multi_device' => true]);
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        Device::factory()->count(2)->create([
            'tenant_id' => $tenant->id,
            'status' => 'connected',
        ]);

        $this->assertFalse($this->deviceService->canAddDevice($tenant));
    }

    public function test_cannot_add_device_without_active_subscription(): void
    {
        $tenant = Tenant::factory()->create();

        $this->assertFalse($this->deviceService->canAddDevice($tenant));
    }

    public function test_pending_devices_count_toward_limit(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['max_devices' => 1, 'has_multi_device' => false]);
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        Device::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'pending',
        ]);

        $this->assertFalse($this->deviceService->canAddDevice($tenant));
    }
}
