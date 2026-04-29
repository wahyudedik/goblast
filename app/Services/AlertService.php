<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\SystemAlertNotification;
use App\Services\Contracts\AlertServiceInterface;
use Illuminate\Support\Facades\Log;

class AlertService implements AlertServiceInterface
{
    /**
     * Valid alert types supported by the system.
     *
     * @var array<int, string>
     */
    public const array VALID_TYPES = [
        'gateway.down',
        'quota.90pct',
        'jobs.failed_spike',
        'subscription.expiring',
        'trial.expiring',
    ];

    /**
     * Valid severity levels for alerts.
     *
     * @var array<int, string>
     */
    public const array VALID_SEVERITIES = [
        'warning',
        'error',
        'critical',
    ];

    /**
     * Create a new alert and notify superadmin users.
     *
     * Creates an Alert record with the given type, severity, message,
     * optional context JSON, and optional tenant association.
     * After creation, calls notifySuperadmin() to email all superadmins.
     */
    public function create(string $type, string $message, string $severity, ?Tenant $tenant = null, ?array $context = null): Alert
    {
        $alert = Alert::create([
            'tenant_id' => $tenant?->id,
            'type' => $type,
            'severity' => $severity,
            'message' => $message,
            'context' => $context,
            'status' => 'active',
        ]);

        Log::info('Alert created', [
            'alert_id' => $alert->id,
            'type' => $type,
            'severity' => $severity,
            'tenant_id' => $tenant?->id,
        ]);

        $this->notifySuperadmin($alert);

        return $alert;
    }

    /**
     * Resolve an alert by marking it as resolved.
     *
     * Sets the status to 'resolved', records the resolving user
     * and the resolution timestamp.
     */
    public function resolve(Alert $alert, User $resolvedBy): void
    {
        $alert->update([
            'status' => 'resolved',
            'resolved_by' => $resolvedBy->id,
            'resolved_at' => now(),
        ]);

        Log::info('Alert resolved', [
            'alert_id' => $alert->id,
            'type' => $alert->type,
            'resolved_by' => $resolvedBy->id,
            'resolution_time_minutes' => $alert->created_at->diffInMinutes(now()),
        ]);
    }

    /**
     * Send a SystemAlertNotification email to all superadmin users.
     *
     * Iterates over all users with role 'superadmin' and sends
     * the notification. Failures are logged but do not halt execution.
     */
    public function notifySuperadmin(Alert $alert): void
    {
        $superadmins = User::where('role', 'superadmin')->get();

        foreach ($superadmins as $superadmin) {
            try {
                $superadmin->notify(new SystemAlertNotification($alert));

                Log::info('Alert notification sent to superadmin', [
                    'alert_id' => $alert->id,
                    'superadmin_id' => $superadmin->id,
                    'superadmin_email' => $superadmin->email,
                ]);
            } catch (\Throwable $e) {
                Log::error('Failed to send alert notification to superadmin', [
                    'alert_id' => $alert->id,
                    'superadmin_id' => $superadmin->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
