<?php

namespace Tests\Unit;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\GoogleAuthService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GoogleAuthServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    private GoogleAuthService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GoogleAuthService;
    }

    public function test_creates_tenant_user_and_subscription_for_new_email(): void
    {
        Plan::factory()->starter()->create();

        $googleUser = [
            'id' => 'google-123456',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'avatar' => 'https://example.com/avatar.jpg',
        ];

        $user = $this->service->findOrCreateUser($googleUser);

        $this->assertNotNull($user->id);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('google-123456', $user->google_id);
        $this->assertEquals('https://example.com/avatar.jpg', $user->google_avatar);
        $this->assertEquals('admin', $user->role);
        $this->assertTrue($user->is_active);
        $this->assertNull($user->password);

        // Verify tenant
        $this->assertNotNull($user->tenant);
        $this->assertEquals('John Doe', $user->tenant->name);
        $this->assertEquals('john@example.com', $user->tenant->email);
        $this->assertEquals('trial', $user->tenant->status);

        // Verify subscription
        $subscription = Subscription::where('tenant_id', $user->tenant->id)->first();
        $this->assertNotNull($subscription);
        $this->assertEquals('active', $subscription->status);
    }

    public function test_updates_google_id_and_avatar_for_existing_user(): void
    {
        $tenant = Tenant::factory()->create();
        $existingUser = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'existing@example.com',
            'google_id' => null,
            'google_avatar' => null,
        ]);

        $googleUser = [
            'id' => 'google-789',
            'name' => 'Different Name',
            'email' => 'existing@example.com',
            'avatar' => 'https://example.com/new-avatar.jpg',
        ];

        $user = $this->service->findOrCreateUser($googleUser);

        $this->assertEquals($existingUser->id, $user->id);
        $this->assertEquals('google-789', $user->google_id);
        $this->assertEquals('https://example.com/new-avatar.jpg', $user->google_avatar);
    }

    public function test_password_not_changed_when_linking_google_account(): void
    {
        $tenant = Tenant::factory()->create();
        $originalPassword = Hash::make('original-password');
        $existingUser = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'user@example.com',
            'password' => $originalPassword,
            'google_id' => null,
        ]);

        $googleUser = [
            'id' => 'google-456',
            'name' => 'User Name',
            'email' => 'user@example.com',
            'avatar' => null,
        ];

        $user = $this->service->findOrCreateUser($googleUser);

        $this->assertEquals($originalPassword, $user->password);
    }

    public function test_email_verified_at_is_set_for_new_user(): void
    {
        Plan::factory()->starter()->create();

        $googleUser = [
            'id' => 'google-111',
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'avatar' => null,
        ];

        $user = $this->service->findOrCreateUser($googleUser);

        $this->assertNotNull($user->email_verified_at);
    }

    public function test_no_new_tenant_created_when_linking_existing_user(): void
    {
        $tenant = Tenant::factory()->create();
        User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'linked@example.com',
        ]);

        $tenantCountBefore = Tenant::count();

        $googleUser = [
            'id' => 'google-222',
            'name' => 'Linked User',
            'email' => 'linked@example.com',
            'avatar' => null,
        ];

        $this->service->findOrCreateUser($googleUser);

        $this->assertEquals($tenantCountBefore, Tenant::count());
    }

    public function test_no_new_subscription_created_when_linking_existing_user(): void
    {
        $tenant = Tenant::factory()->create();
        User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'sub@example.com',
        ]);

        $subscriptionCountBefore = Subscription::count();

        $googleUser = [
            'id' => 'google-333',
            'name' => 'Sub User',
            'email' => 'sub@example.com',
            'avatar' => null,
        ];

        $this->service->findOrCreateUser($googleUser);

        $this->assertEquals($subscriptionCountBefore, Subscription::count());
    }

    public function test_handles_null_avatar_gracefully(): void
    {
        Plan::factory()->starter()->create();

        $googleUser = [
            'id' => 'google-444',
            'name' => 'No Avatar User',
            'email' => 'noavatar@example.com',
            'avatar' => null,
        ];

        $user = $this->service->findOrCreateUser($googleUser);

        $this->assertNull($user->google_avatar);
        $this->assertEquals('google-444', $user->google_id);
    }

    public function test_subscription_uses_starter_plan(): void
    {
        $starterPlan = Plan::factory()->starter()->create();
        Plan::factory()->pro()->create();

        $googleUser = [
            'id' => 'google-555',
            'name' => 'Plan User',
            'email' => 'plan@example.com',
            'avatar' => null,
        ];

        $user = $this->service->findOrCreateUser($googleUser);

        $subscription = Subscription::where('tenant_id', $user->tenant->id)->first();
        $this->assertEquals($starterPlan->id, $subscription->plan_id);
    }

    public function test_subscription_duration_matches_config(): void
    {
        Plan::factory()->starter()->create();
        $trialDays = config('wa-automation.subscription.trial_duration_days', 14);

        $googleUser = [
            'id' => 'google-666',
            'name' => 'Duration User',
            'email' => 'duration@example.com',
            'avatar' => null,
        ];

        $user = $this->service->findOrCreateUser($googleUser);

        $subscription = Subscription::where('tenant_id', $user->tenant->id)->first();
        $expectedEndDate = $subscription->starts_at->addDays($trialDays);

        $this->assertTrue($subscription->ends_at->isSameDay($expectedEndDate));
    }

    public function test_user_role_is_admin_for_new_registration(): void
    {
        Plan::factory()->starter()->create();

        $googleUser = [
            'id' => 'google-777',
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'avatar' => null,
        ];

        $user = $this->service->findOrCreateUser($googleUser);

        $this->assertEquals('admin', $user->role);
    }

    public function test_user_is_active_for_new_registration(): void
    {
        Plan::factory()->starter()->create();

        $googleUser = [
            'id' => 'google-888',
            'name' => 'Active User',
            'email' => 'active@example.com',
            'avatar' => null,
        ];

        $user = $this->service->findOrCreateUser($googleUser);

        $this->assertTrue($user->is_active);
    }

    public function test_tenant_status_is_trial_for_new_registration(): void
    {
        Plan::factory()->starter()->create();

        $googleUser = [
            'id' => 'google-999',
            'name' => 'Trial User',
            'email' => 'trial@example.com',
            'avatar' => null,
        ];

        $user = $this->service->findOrCreateUser($googleUser);

        $this->assertEquals('trial', $user->tenant->status);
    }
}
