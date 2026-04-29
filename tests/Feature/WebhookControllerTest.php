<?php

namespace Tests\Feature;

use App\Jobs\ProcessWebhookJob;
use App\Models\Device;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_accepts_valid_webhook_with_correct_signature(): void
    {
        // Arrange
        Queue::fake();

        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create([
            'tenant_id' => $tenant->id,
            'gateway_device_id' => 'test-device-123',
        ]);

        $payload = [
            'event' => 'message.received',
            'device_id' => 'test-device-123',
            'from' => '6281234567890',
            'message' => 'test message',
            'timestamp' => now()->timestamp * 1000,
        ];

        $secret = config('wa-automation.baileys.webhook_secret');
        $signature = hash_hmac('sha256', json_encode($payload), $secret);

        // Act
        $response = $this->postJson('/webhook/baileys', $payload, [
            'X-Baileys-Signature' => $signature,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Webhook processed',
        ]);

        Queue::assertPushed(ProcessWebhookJob::class, function ($job) use ($payload) {
            return $job->payload === $payload;
        });
    }

    public function test_rejects_webhook_with_invalid_signature(): void
    {
        // Arrange
        Queue::fake();

        $payload = [
            'event' => 'message.received',
            'device_id' => 'test-device-123',
            'from' => '6281234567890',
            'message' => 'test message',
        ];

        // Act
        $response = $this->postJson('/webhook/baileys', $payload, [
            'X-Baileys-Signature' => 'invalid-signature',
        ]);

        // Assert
        $response->assertStatus(401);
        $response->assertJson([
            'error' => 'Invalid signature',
        ]);

        Queue::assertNothingPushed();
    }

    public function test_rejects_webhook_without_signature(): void
    {
        // Arrange
        Queue::fake();

        $payload = [
            'event' => 'message.received',
            'device_id' => 'test-device-123',
            'from' => '6281234567890',
            'message' => 'test message',
        ];

        // Act
        $response = $this->postJson('/webhook/baileys', $payload);

        // Assert
        $response->assertStatus(401);
        $response->assertJson([
            'error' => 'Invalid signature',
        ]);

        Queue::assertNothingPushed();
    }

    public function test_rejects_malformed_payload(): void
    {
        // Arrange
        Queue::fake();

        $payload = [
            'event' => 'message.received',
            // Missing required fields
        ];

        $secret = config('wa-automation.baileys.webhook_secret');
        $signature = hash_hmac('sha256', json_encode($payload), $secret);

        // Act
        $response = $this->postJson('/webhook/baileys', $payload, [
            'X-Baileys-Signature' => $signature,
        ]);

        // Assert
        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'Malformed payload',
        ]);

        Queue::assertNothingPushed();
    }

    public function test_webhook_route_exists(): void
    {
        // Act
        $response = $this->post('/webhook/baileys');

        // Assert - Route exists (even if it returns 401 due to missing signature)
        $response->assertStatus(401);
    }
}
