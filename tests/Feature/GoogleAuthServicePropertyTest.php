<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\GoogleAuthService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Property-based tests for GoogleAuthService.
 *
 * These tests validate correctness properties using multiple random inputs.
 */
class GoogleAuthServicePropertyTest extends TestCase
{
    use LazilyRefreshDatabase;

    private GoogleAuthService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GoogleAuthService;
    }

    /**
     * Feature: gmail-login, Property 1: Registrasi pengguna baru via Google menghasilkan setup tenant yang lengkap
     *
     * For any valid Google profile data where the email doesn't exist in the database,
     * calling findOrCreateUser must result in:
     * - One new Tenant with name and email matching the Google profile
     * - One new User with role=admin, is_active=true, google_id filled, password=null, email_verified_at not null
     * - One new Subscription with status=active, connected to Starter Plan, duration matching config
     *
     * Validates: Requirements 3.1, 3.2, 3.3, 3.4
     */
    public function test_property_new_user_registration_creates_complete_tenant_setup(): void
    {
        // Create starter plan for trial subscriptions
        Plan::factory()->starter()->create();

        $trialDays = config('wa-automation.subscription.trial_duration_days', 14);
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            // Generate random Google profile
            $googleUser = $this->generateRandomGoogleProfile();

            // Ensure email doesn't exist
            $this->assertNull(User::where('email', $googleUser['email'])->first());

            // Execute
            $user = $this->service->findOrCreateUser($googleUser);

            // Verify User
            $this->assertNotNull($user->id, "Iteration $i: User should be created");
            $this->assertEquals($googleUser['email'], $user->email, "Iteration $i: Email should match");
            $this->assertEquals($googleUser['name'], $user->name, "Iteration $i: Name should match");
            $this->assertEquals($googleUser['id'], $user->google_id, "Iteration $i: Google ID should match");
            $this->assertEquals($googleUser['avatar'], $user->google_avatar, "Iteration $i: Avatar should match");
            $this->assertEquals('admin', $user->role, "Iteration $i: Role should be admin");
            $this->assertTrue($user->is_active, "Iteration $i: User should be active");
            $this->assertNull($user->password, "Iteration $i: Password should be null");
            $this->assertNotNull($user->email_verified_at, "Iteration $i: Email should be verified");

            // Verify Tenant
            $tenant = $user->tenant;
            $this->assertNotNull($tenant, "Iteration $i: Tenant should exist");
            $this->assertEquals($googleUser['name'], $tenant->name, "Iteration $i: Tenant name should match");
            $this->assertEquals($googleUser['email'], $tenant->email, "Iteration $i: Tenant email should match");
            $this->assertEquals('trial', $tenant->status, "Iteration $i: Tenant status should be trial");

            // Verify Subscription
            $subscription = Subscription::where('tenant_id', $tenant->id)->first();
            $this->assertNotNull($subscription, "Iteration $i: Subscription should exist");
            $this->assertEquals('active', $subscription->status, "Iteration $i: Subscription should be active");
            $this->assertEquals('starter', $subscription->plan->slug, "Iteration $i: Plan should be starter");

            // Verify subscription duration matches config
            $expectedEndDate = $subscription->starts_at->addDays($trialDays);
            $this->assertTrue(
                $subscription->ends_at->isSameDay($expectedEndDate),
                "Iteration $i: Subscription duration should match config ($trialDays days)"
            );
        }
    }

    /**
     * Feature: gmail-login, Property 2: Menghubungkan akun Google ke user yang sudah ada memperbarui data Google tanpa mengubah data lainnya
     *
     * For any user already registered in the database (with or without password),
     * when findOrCreateUser is called with a Google profile having the same email:
     * - google_id and google_avatar must be updated to match the Google profile
     * - password, name, role, is_active, tenant_id and other attributes must remain unchanged
     * - No new Tenant or Subscription should be created
     *
     * Validates: Requirements 2.2, 8.3
     */
    public function test_property_linking_google_account_updates_only_google_fields(): void
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            // Create a random existing user with tenant
            $tenant = Tenant::factory()->create();
            $originalPassword = Hash::make('original-password-'.$i);
            $existingUser = User::factory()->create([
                'tenant_id' => $tenant->id,
                'password' => $originalPassword,
                'role' => fake()->randomElement(['admin', 'member']),
                'is_active' => fake()->boolean(80),
                'google_id' => null,
                'google_avatar' => null,
            ]);

            // Store original values
            $originalName = $existingUser->name;
            $originalRole = $existingUser->role;
            $originalIsActive = $existingUser->is_active;
            $originalTenantId = $existingUser->tenant_id;
            $originalEmailVerifiedAt = $existingUser->email_verified_at;

            // Count existing tenants and subscriptions
            $tenantCountBefore = Tenant::count();
            $subscriptionCountBefore = Subscription::count();

            // Generate Google profile with same email
            $googleUser = [
                'id' => fake()->unique()->numerify('google-##########'),
                'name' => fake()->name(), // Different name
                'email' => $existingUser->email, // Same email
                'avatar' => fake()->imageUrl(200, 200, 'people'),
            ];

            // Execute
            $user = $this->service->findOrCreateUser($googleUser);

            // Verify only google_id and google_avatar changed
            $this->assertEquals($existingUser->id, $user->id, "Iteration $i: Should be same user");
            $this->assertEquals($googleUser['id'], $user->google_id, "Iteration $i: Google ID should be updated");
            $this->assertEquals($googleUser['avatar'], $user->google_avatar, "Iteration $i: Avatar should be updated");

            // Verify other fields remain unchanged
            $this->assertEquals($originalName, $user->name, "Iteration $i: Name should not change");
            $this->assertEquals($originalPassword, $user->password, "Iteration $i: Password should not change");
            $this->assertEquals($originalRole, $user->role, "Iteration $i: Role should not change");
            $this->assertEquals($originalIsActive, $user->is_active, "Iteration $i: is_active should not change");
            $this->assertEquals($originalTenantId, $user->tenant_id, "Iteration $i: tenant_id should not change");

            // Verify email_verified_at is preserved (not overwritten)
            if ($originalEmailVerifiedAt) {
                $this->assertEquals(
                    $originalEmailVerifiedAt->toDateTimeString(),
                    $user->email_verified_at->toDateTimeString(),
                    "Iteration $i: email_verified_at should not change"
                );
            }

            // Verify no new Tenant or Subscription created
            $this->assertEquals($tenantCountBefore, Tenant::count(), "Iteration $i: No new tenant should be created");
            $this->assertEquals($subscriptionCountBefore, Subscription::count(), "Iteration $i: No new subscription should be created");
        }
    }

    /**
     * Generate a random Google profile for testing.
     *
     * @return array{id: string, name: string, email: string, avatar: string|null}
     */
    private function generateRandomGoogleProfile(): array
    {
        return [
            'id' => fake()->unique()->numerify('google-##########'),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'avatar' => fake()->optional(0.8)->imageUrl(200, 200, 'people'),
        ];
    }

    /**
     * Feature: gmail-login, Property 3: User tanpa password tidak dapat login via form email/password
     *
     * For any user with password=null and google_id filled,
     * when attempting authentication via email/password form,
     * the system must reject login and return the error message
     * "Akun ini terdaftar menggunakan Google. Silakan login dengan Google."
     *
     * Validates: Requirements 8.2
     */
    public function test_property_google_only_users_cannot_login_via_password_form(): void
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            // Create a Google-only user (no password)
            $tenant = Tenant::factory()->create();
            $googleOnlyUser = User::factory()->create([
                'tenant_id' => $tenant->id,
                'email' => fake()->unique()->safeEmail(),
                'password' => null,
                'google_id' => fake()->numerify('google-##########'),
            ]);

            // Attempt to login via password form
            $response = $this->post('/login', [
                'email' => $googleOnlyUser->email,
                'password' => 'any-password',
            ]);

            // Should be redirected back with error
            $response->assertSessionHasErrors('email');

            // Verify the specific error message
            $errors = session('errors');
            $this->assertNotNull($errors, "Iteration $i: Should have validation errors");
            $this->assertStringContainsString(
                'Akun ini terdaftar menggunakan Google',
                $errors->first('email'),
                "Iteration $i: Error message should mention Google login"
            );

            // Verify user is not authenticated
            $this->assertGuest();
        }
    }
}
