<?php

namespace Tests\Unit;

use App\Models\Alert;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\SystemAlertNotification;
use App\Services\AlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AlertServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AlertService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AlertService::class);
    }

    // ── create() ────────────────────────────────────────────────────

    public function test_create_alert_persists_record_with_correct_attributes(): void
    {
        Notification::fake();

        $alert = $this->service->create(
            type: 'gateway.down',
            message: 'Gateway instance is not responding',
            severity: 'critical',
        );

        $this->assertInstanceOf(Alert::class, $alert);
        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'type' => 'gateway.down',
            'severity' => 'critical',
            'message' => 'Gateway instance is not responding',
            'status' => 'active',
        ]);
        $this->assertNull($alert->tenant_id);
        $this->assertNull($alert->resolved_by);
        $this->assertNull($alert->resolved_at);
    }

    public function test_create_alert_with_tenant_association(): void
    {
        Notification::fake();

        $tenant = Tenant::factory()->create();

        $alert = $this->service->create(
            type: 'quota.90pct',
            message: 'Tenant quota usage exceeds 90%',
            severity: 'warning',
            tenant: $tenant,
        );

        $this->assertEquals($tenant->id, $alert->tenant_id);
        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'tenant_id' => $tenant->id,
            'type' => 'quota.90pct',
            'severity' => 'warning',
        ]);
    }

    public function test_create_alert_with_context_data(): void
    {
        Notification::fake();

        $context = [
            'gateway_instance_id' => 1,
            'gateway_name' => 'Primary Gateway',
            'error' => 'Connection refused',
        ];

        $alert = $this->service->create(
            type: 'gateway.down',
            message: 'Gateway is down',
            severity: 'critical',
            context: $context,
        );

        $this->assertEquals($context, $alert->context);
    }

    public function test_create_alert_sends_notification_to_superadmins(): void
    {
        Notification::fake();

        $superadmin = User::factory()->superadmin()->create();

        $alert = $this->service->create(
            type: 'jobs.failed_spike',
            message: '55 failed jobs in the last hour',
            severity: 'error',
        );

        Notification::assertSentTo($superadmin, SystemAlertNotification::class);
    }

    public function test_create_alert_sends_notification_to_multiple_superadmins(): void
    {
        Notification::fake();

        $superadminA = User::factory()->superadmin()->create();
        $superadminB = User::factory()->superadmin()->create();

        $alert = $this->service->create(
            type: 'gateway.down',
            message: 'Gateway down',
            severity: 'critical',
        );

        Notification::assertSentTo($superadminA, SystemAlertNotification::class);
        Notification::assertSentTo($superadminB, SystemAlertNotification::class);
    }

    public function test_create_alert_does_not_notify_non_superadmin_users(): void
    {
        Notification::fake();

        $tenant = Tenant::factory()->create();
        $adminUser = User::factory()->admin()->create(['tenant_id' => $tenant->id]);
        $memberUser = User::factory()->member()->create(['tenant_id' => $tenant->id]);
        $superadmin = User::factory()->superadmin()->create();

        $this->service->create(
            type: 'quota.90pct',
            message: 'Quota warning',
            severity: 'warning',
            tenant: $tenant,
        );

        Notification::assertSentTo($superadmin, SystemAlertNotification::class);
        Notification::assertNotSentTo($adminUser, SystemAlertNotification::class);
        Notification::assertNotSentTo($memberUser, SystemAlertNotification::class);
    }

    public function test_create_alert_without_superadmins_does_not_fail(): void
    {
        Notification::fake();

        // No superadmin users exist — should not throw
        $alert = $this->service->create(
            type: 'trial.expiring',
            message: 'Trial expiring soon',
            severity: 'warning',
        );

        $this->assertInstanceOf(Alert::class, $alert);
        Notification::assertNothingSent();
    }

    public function test_create_alert_supports_all_valid_types(): void
    {
        Notification::fake();

        foreach (AlertService::VALID_TYPES as $type) {
            $alert = $this->service->create(
                type: $type,
                message: "Test alert for {$type}",
                severity: 'warning',
            );

            $this->assertEquals($type, $alert->type);
        }

        $this->assertDatabaseCount('alerts', count(AlertService::VALID_TYPES));
    }

    public function test_create_alert_supports_all_severity_levels(): void
    {
        Notification::fake();

        foreach (AlertService::VALID_SEVERITIES as $severity) {
            $alert = $this->service->create(
                type: 'gateway.down',
                message: "Test alert with severity {$severity}",
                severity: $severity,
            );

            $this->assertEquals($severity, $alert->severity);
        }

        $this->assertDatabaseCount('alerts', count(AlertService::VALID_SEVERITIES));
    }

    // ── resolve() ───────────────────────────────────────────────────

    public function test_resolve_alert_updates_status_and_resolution_data(): void
    {
        $alert = Alert::factory()->create([
            'type' => 'gateway.down',
            'severity' => 'critical',
            'status' => 'active',
        ]);

        $superadmin = User::factory()->superadmin()->create();

        $this->service->resolve($alert, $superadmin);

        $alert->refresh();

        $this->assertEquals('resolved', $alert->status);
        $this->assertEquals($superadmin->id, $alert->resolved_by);
        $this->assertNotNull($alert->resolved_at);
        $this->assertTrue($alert->resolved_at->isToday());
    }

    public function test_resolve_alert_tracks_resolution_time(): void
    {
        $alert = Alert::factory()->create([
            'type' => 'jobs.failed_spike',
            'severity' => 'error',
            'status' => 'active',
            'created_at' => now()->subMinutes(30),
        ]);

        $superadmin = User::factory()->superadmin()->create();

        $this->service->resolve($alert, $superadmin);

        $alert->refresh();

        $this->assertNotNull($alert->resolved_at);
        // Resolution time should be approximately 30 minutes
        $resolutionMinutes = $alert->created_at->diffInMinutes($alert->resolved_at);
        $this->assertGreaterThanOrEqual(29, $resolutionMinutes);
        $this->assertLessThanOrEqual(31, $resolutionMinutes);
    }

    public function test_resolve_alert_can_be_resolved_by_any_user(): void
    {
        $tenant = Tenant::factory()->create();
        $alert = Alert::factory()->create([
            'status' => 'active',
        ]);

        $superadmin = User::factory()->superadmin()->create();

        $this->service->resolve($alert, $superadmin);

        $alert->refresh();

        $this->assertEquals('resolved', $alert->status);
        $this->assertEquals($superadmin->id, $alert->resolved_by);
    }

    // ── notifySuperadmin() ──────────────────────────────────────────

    public function test_notify_superadmin_sends_notification_to_all_superadmins(): void
    {
        Notification::fake();

        $superadminA = User::factory()->superadmin()->create();
        $superadminB = User::factory()->superadmin()->create();

        $alert = Alert::factory()->create([
            'type' => 'gateway.down',
            'severity' => 'critical',
            'message' => 'Gateway is not responding',
        ]);

        $this->service->notifySuperadmin($alert);

        Notification::assertSentTo($superadminA, SystemAlertNotification::class);
        Notification::assertSentTo($superadminB, SystemAlertNotification::class);
    }

    public function test_notify_superadmin_does_not_send_to_non_superadmin_users(): void
    {
        Notification::fake();

        $tenant = Tenant::factory()->create();
        $adminUser = User::factory()->admin()->create(['tenant_id' => $tenant->id]);
        $superadmin = User::factory()->superadmin()->create();

        $alert = Alert::factory()->create();

        $this->service->notifySuperadmin($alert);

        Notification::assertSentTo($superadmin, SystemAlertNotification::class);
        Notification::assertNotSentTo($adminUser, SystemAlertNotification::class);
    }

    public function test_notify_superadmin_handles_no_superadmins_gracefully(): void
    {
        Notification::fake();

        $alert = Alert::factory()->create();

        // Should not throw when no superadmins exist
        $this->service->notifySuperadmin($alert);

        Notification::assertNothingSent();
    }
}
