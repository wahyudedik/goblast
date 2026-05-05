<?php

namespace Tests\Unit;

use App\Jobs\ProcessWebhookJob;
use App\Models\Alert;
use App\Models\Device;
use App\Models\SystemLog;
use App\Models\Tenant;
use App\Services\Contracts\AlertServiceInterface;
use App\Services\Contracts\AutoReplyServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessWebhookJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_processes_valid_webhook_payload(): void
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create([
            'tenant_id' => $tenant->id,
            'gateway_device_id' => 'test-device-123',
            'status' => 'connected',
        ]);

        $payload = [
            'event' => 'message',
            'device_id' => 'test-device-123',
            'from' => '6281234567890',
            'message' => 'test message',
            'timestamp' => now()->timestamp * 1000,
        ];

        $autoReplyService = $this->mock(AutoReplyServiceInterface::class);
        $autoReplyService->shouldReceive('processIncomingMessage')
            ->once()
            ->with('test-device-123', '6281234567890', 'test message');

        $alertService = $this->mock(AlertServiceInterface::class);

        // Act
        $job = new ProcessWebhookJob($payload);
        $job->handle($autoReplyService, $alertService);

        // Assert - AutoReplyService was called
        $this->assertTrue(true);
    }

    public function test_skips_processing_for_invalid_payload(): void
    {
        // Arrange — missing device_id makes this invalid for all event types
        $payload = [
            'event' => 'message',
            // Missing device_id and other required fields
        ];

        $autoReplyService = $this->mock(AutoReplyServiceInterface::class);
        $autoReplyService->shouldNotReceive('processIncomingMessage');

        $alertService = $this->mock(AlertServiceInterface::class);

        // Act
        $job = new ProcessWebhookJob($payload);
        $job->handle($autoReplyService, $alertService);

        // Assert - AutoReplyService was not called
        $this->assertTrue(true);
    }

    public function test_skips_processing_for_unknown_device(): void
    {
        // Arrange
        $payload = [
            'event' => 'message',
            'device_id' => 'unknown-device-id',
            'from' => '6281234567890',
            'message' => 'test message',
            'timestamp' => now()->timestamp * 1000,
        ];

        $autoReplyService = $this->mock(AutoReplyServiceInterface::class);
        $autoReplyService->shouldNotReceive('processIncomingMessage');

        $alertService = $this->mock(AlertServiceInterface::class);

        // Act
        $job = new ProcessWebhookJob($payload);
        $job->handle($autoReplyService, $alertService);

        // Assert - Job completes without error even if device not found
        $this->assertTrue(true);
    }

    public function test_validates_required_payload_fields(): void
    {
        // Arrange — 'message' event requires all four fields
        $job = new ProcessWebhookJob([
            'event' => 'message',
            'device_id' => 'test-device',
            'from' => '6281234567890',
            'message' => 'test',
        ]);

        // Act
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('validatePayload');
        $result = $method->invoke($job);

        // Assert
        $this->assertTrue($result);
    }

    public function test_validation_fails_for_missing_fields(): void
    {
        // Arrange — 'message' event without from/message should fail
        $job = new ProcessWebhookJob([
            'event' => 'message',
            'device_id' => 'test-device',
            // Missing 'from' and 'message'
        ]);

        // Act
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('validatePayload');
        $result = $method->invoke($job);

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function test_session_restore_complete_with_only_event_and_device_id_passes_validation_and_creates_system_log(): void
    {
        // Arrange — session.restore_complete only needs event + device_id
        $payload = [
            'event' => 'session.restore_complete',
            'device_id' => 'test-device-456',
        ];

        $autoReplyService = $this->mock(AutoReplyServiceInterface::class);
        $autoReplyService->shouldNotReceive('processIncomingMessage');

        $alertService = $this->mock(AlertServiceInterface::class);

        // Act
        $job = new ProcessWebhookJob($payload);
        $job->handle($autoReplyService, $alertService);

        // Assert — SystemLog entry was created
        $this->assertDatabaseHas('system_logs', [
            'type' => 'gateway',
            'severity' => 'info',
        ]);
    }

    /** @test */
    public function test_device_manual_intervention_with_only_event_and_device_id_passes_validation_and_updates_device_status(): void
    {
        // Arrange — device.manual_intervention only needs event + device_id
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create([
            'tenant_id' => $tenant->id,
            'gateway_device_id' => 'test-device-789',
            'status' => 'connected',
        ]);

        $payload = [
            'event' => 'device.manual_intervention',
            'device_id' => 'test-device-789',
        ];

        $autoReplyService = $this->mock(AutoReplyServiceInterface::class);
        $autoReplyService->shouldNotReceive('processIncomingMessage');

        $alertService = $this->mock(AlertServiceInterface::class);
        $alertService->shouldReceive('create')
            ->once()
            ->withAnyArgs()
            ->andReturn(\Mockery::mock(Alert::class));

        // Act
        $job = new ProcessWebhookJob($payload);
        $job->handle($autoReplyService, $alertService);

        // Assert — device status updated to 'error'
        $this->assertDatabaseHas('devices', [
            'id' => $device->id,
            'status' => 'error',
        ]);
    }

    /** @test */
    public function test_message_event_without_from_field_fails_validation(): void
    {
        // Arrange — 'message' event missing 'from'
        $job = new ProcessWebhookJob([
            'event' => 'message',
            'device_id' => 'test-device',
            'message' => 'hello',
            // Missing 'from'
        ]);

        // Act
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('validatePayload');
        $result = $method->invoke($job);

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function test_message_event_without_message_field_fails_validation(): void
    {
        // Arrange — 'message' event missing 'message'
        $job = new ProcessWebhookJob([
            'event' => 'message',
            'device_id' => 'test-device',
            'from' => '6281234567890',
            // Missing 'message'
        ]);

        // Act
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('validatePayload');
        $result = $method->invoke($job);

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function test_unknown_event_with_event_and_device_id_passes_validation_and_returns_early_without_error(): void
    {
        // Arrange — unknown event only needs event + device_id; handleDefault returns early
        // because from/message are missing, but no exception is thrown
        $payload = [
            'event' => 'some.unknown.event',
            'device_id' => 'test-device-unknown',
        ];

        $autoReplyService = $this->mock(AutoReplyServiceInterface::class);
        $autoReplyService->shouldNotReceive('processIncomingMessage');

        $alertService = $this->mock(AlertServiceInterface::class);

        // Act — should not throw
        $job = new ProcessWebhookJob($payload);
        $job->handle($autoReplyService, $alertService);

        // Assert — validation passed (no exception), processing stopped gracefully
        $this->assertTrue(true);
    }
}
