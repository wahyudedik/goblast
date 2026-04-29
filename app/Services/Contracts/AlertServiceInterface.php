<?php

namespace App\Services\Contracts;

use App\Models\Alert;
use App\Models\Tenant;
use App\Models\User;

interface AlertServiceInterface
{
    /**
     * Create a new alert with the given type, message, severity, and optional tenant.
     *
     * After creation, sends a notification to all superadmin users.
     *
     * @param  array<string, mixed>|null  $context  Additional context data for the alert
     */
    public function create(string $type, string $message, string $severity, ?Tenant $tenant = null, ?array $context = null): Alert;

    /**
     * Resolve an alert by marking it as resolved with the resolving user.
     */
    public function resolve(Alert $alert, User $resolvedBy): void;

    /**
     * Send a SystemAlertNotification email to all superadmin users.
     */
    public function notifySuperadmin(Alert $alert): void;
}
