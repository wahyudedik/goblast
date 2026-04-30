<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Device;
use App\Models\SystemLog;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\SystemAlertNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebhookNewEventsTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * Generate a valid HMAC signature for the given payload.
     *
     * @param  array<string, mixed>  $payload
     */
    private function generateSignature(array $payload): string
    {
        $secret = config('wa-automation.baileys.webhook_secret');

        return hash_hmac('sha256', json_encode($payload), $secret);
    }

    #[Test]
    public function session_restore_complete_webhook_is_accepted_and_logged(): void
    {
        $payload = [
            'event' => 'session.restore_complete',
            'device_id' => 'system',
            'from' => 'system',
            'message' => 'Session restoration completed',
            'stats' => ['total' => 10, 'restored' => 8, 'failed' => 2],
            'timestamp' => '2025-01-15T10:30:00.000Z',
        ];

        $signature = $this->generateSignature($payload);

        // With sync queue, the job runs inline during the POST
        $response = $this->postJson('/webhook/baileys', $payload, [
            'X-Baileys-Signature' => $signature,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Webhook processed',
        ]);

        $this->assertDatabaseHas('system_logs', [
            'type' => 'gateway',
            'severity' => 'info',
            'message' => 'Session restoration completed',
        ]);

        $log = SystemLog::where('type', 'gateway')
            ->where('message', 'Session restoration completed')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('session.restore_complete', $log->context['event']);
        $this->assertEquals(10, $log->context['stats']['total']);
        $this->assertEquals(8, $log->context['stats']['restored']);
        $this->assertEquals(2, $log->context['stats']['failed']);
    }

    #[Test]
    public function device_manual_intervention_webhook_updates_device_and_creates_alert(): void
    {
        Notification::fake();

        $tenant = Tenant::factory()->create();
        User::factory()->for($tenant)->create(['role' => 'superadmin']);

        $device = Device::factory()->for($tenant)->connected()->create([
            'gateway_device_id' => 'test-device-uuid',
        ]);

        $payload = [
            'event' => 'device.manual_intervention',
            'device_id' => 'test-device-uuid',
            'from' => 'test-device-uuid',
            'message' => 'Device requires manual intervention after 10 failed connection attempts',
            'status' => 'manual_intervention_required',
            'failure_count' => 10,
            'last_error' => 'Connection timeout',
            'timestamp' => '2025-01-15T10:30:00.000Z',
        ];

        $signature = $this->generateSignature($payload);

        $response = $this->postJson('/webhook/baileys', $payload, [
            'X-Baileys-Signature' => $signature,
        ]);

        $response->assertOk();

        // Verify device status was updated to 'error'
        $device->refresh();
        $this->assertEquals('error', $device->status);

        // Verify alert was created
        $this->assertDatabaseHas('alerts', [
            'tenant_id' => $tenant->id,
            'type' => 'gateway.down',
            'severity' => 'critical',
            'status' => 'active',
        ]);

        $alert = Alert::where('tenant_id', $tenant->id)
            ->where('type', 'gateway.down')
            ->first();

        $this->assertNotNull($alert);
        $this->assertEquals($device->id, $alert->context['device_id']);
        $this->assertEquals('test-device-uuid', $alert->context['gateway_device_id']);
        $this->assertEquals('manual_intervention_required', $alert->context['status']);
        $this->assertEquals(10, $alert->context['failure_count']);
        $this->assertEquals('Connection timeout', $alert->context['last_error']);

        // Verify superadmin was notified
        Notification::assertSentTo(
            User::where('role', 'superadmin')->first(),
            SystemAlertNotification::class,
        );
    }

    #[Test]
    public function device_manual_intervention_webhook_ignores_unknown_device(): void
    {
        $payload = [
            'event' => 'device.manual_intervention',
            'device_id' => 'non-existent-device-uuid',
            'from' => 'non-existent-device-uuid',
            'message' => 'Device requires manual intervention',
            'status' => 'manual_intervention_required',
            'failure_count' => 10,
            'last_error' => 'Connection timeout',
            'timestamp' => '2025-01-15T10:30:00.000Z',
        ];

        $signature = $this->generateSignature($payload);

        $response = $this->postJson('/webhook/baileys', $payload, [
            'X-Baileys-Signature' => $signature,
        ]);

        $response->assertOk();

        // No alert should be created for unknown device
        $this->assertDatabaseCount('alerts', 0);
    }
}
