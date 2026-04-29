<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Contracts\GoogleAuthServiceInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class GoogleAuthService implements GoogleAuthServiceInterface
{
    /**
     * Find or create a user based on Google OAuth profile data.
     *
     * If the email exists, updates google_id and google_avatar.
     * If the email doesn't exist, creates a new Tenant, User, and Subscription.
     *
     * @param  array{
     *     id: string,
     *     name: string,
     *     email: string,
     *     avatar: string|null,
     * }  $googleUser
     */
    public function findOrCreateUser(array $googleUser): User
    {
        $existingUser = User::where('email', $googleUser['email'])->first();

        if ($existingUser) {
            $existingUser->update([
                'google_id' => $googleUser['id'],
                'google_avatar' => $googleUser['avatar'],
            ]);

            return $existingUser->fresh();
        }

        try {
            return $this->createTenantWithUser($googleUser);
        } catch (QueryException $e) {
            // Handle race condition: if email was created by another process
            if ($this->isUniqueViolation($e)) {
                $user = User::where('email', $googleUser['email'])->first();

                if ($user) {
                    $user->update([
                        'google_id' => $googleUser['id'],
                        'google_avatar' => $googleUser['avatar'],
                    ]);

                    return $user->fresh();
                }
            }

            throw $e;
        }
    }

    /**
     * Create a new tenant, user, and trial subscription.
     *
     * @param  array{
     *     id: string,
     *     name: string,
     *     email: string,
     *     avatar: string|null,
     * }  $googleUser
     */
    private function createTenantWithUser(array $googleUser): User
    {
        return DB::transaction(function () use ($googleUser) {
            $trialDays = config('wa-automation.subscription.trial_duration_days', 14);

            // Create tenant with trial status
            $tenant = Tenant::create([
                'name' => $googleUser['name'],
                'email' => $googleUser['email'],
                'status' => 'trial',
                'trial_ends_at' => now()->addDays($trialDays),
            ]);

            // Create user associated with tenant
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $googleUser['name'],
                'email' => $googleUser['email'],
                'password' => null,
                'role' => 'admin',
                'is_active' => true,
                'google_id' => $googleUser['id'],
                'google_avatar' => $googleUser['avatar'],
            ]);

            // Mark email as verified since Google already verified it
            $user->markEmailAsVerified();

            // Get starter plan for trial
            $starterPlan = Plan::where('slug', 'starter')->first();

            if ($starterPlan) {
                // Create trial subscription
                Subscription::create([
                    'tenant_id' => $tenant->id,
                    'plan_id' => $starterPlan->id,
                    'status' => 'active',
                    'message_quota_used' => 0,
                    'message_quota_limit' => $starterPlan->message_quota,
                    'starts_at' => now(),
                    'ends_at' => now()->addDays($trialDays),
                ]);
            }

            return $user;
        });
    }

    /**
     * Check if the exception is a unique constraint violation.
     */
    private function isUniqueViolation(QueryException $e): bool
    {
        // MySQL: 1062 (Duplicate entry)
        // PostgreSQL: 23505 (unique_violation)
        // SQLite: 19 (SQLITE_CONSTRAINT) or 2067 (SQLITE_CONSTRAINT_UNIQUE)
        $errorCode = $e->errorInfo[1] ?? null;

        return in_array($errorCode, [1062, 23505, 19, 2067], true);
    }
}
