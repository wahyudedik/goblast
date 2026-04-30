<?php

namespace Tests\Unit;

use App\Jobs\ProcessWebhookJob;
use App\Models\Device;
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
            'event' => 'message.received',
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
        // Arrange
        $payload = [
            'event' => 'message.received',
            // Missing required fields
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
            'event' => 'message.received',
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
        // Arrange
        $job = new ProcessWebhookJob([
            'event' => 'message.received',
            'device_id' => 'test-device',
            'from' => '6281234567890',
            'message' => 'test',
        ]);

        // Act
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('validatePayload');
        $method->setAccessible(true);
        $result = $method->invoke($job);

        // Assert
        $this->assertTrue($result);
    }

    public function test_validation_fails_for_missing_fields(): void
    {
        // Arrange
        $job = new ProcessWebhookJob([
            'event' => 'message.received',
            // Missing other required fields
        ]);

        // Act
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('validatePayload');
        $method->setAccessible(true);
        $result = $method->invoke($job);

        // Assert
        $this->assertFalse($result);
    }
}
