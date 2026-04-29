<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class GoogleAuthControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_redirect_to_google_returns_redirect_response(): void
    {
        Socialite::fake('google');

        $response = $this->get(route('auth.google.redirect'));

        $response->assertRedirect();
    }

    public function test_callback_logs_in_existing_user_and_redirects_to_dashboard(): void
    {
        $tenant = Tenant::factory()->create();
        $existingUser = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'existing@example.com',
            'google_id' => null,
        ]);

        Socialite::fake('google', (new SocialiteUser)->map([
            'id' => 'google-123',
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'avatar' => 'https://example.com/avatar.jpg',
        ]));

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($existingUser);

        // Verify google_id was updated
        $existingUser->refresh();
        $this->assertEquals('google-123', $existingUser->google_id);
    }

    public function test_callback_registers_new_user_with_tenant_and_subscription(): void
    {
        Plan::factory()->starter()->create();

        Socialite::fake('google', (new SocialiteUser)->map([
            'id' => 'google-456',
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'avatar' => 'https://example.com/avatar.jpg',
        ]));

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('dashboard'));

        // Verify user was created
        $user = User::where('email', 'newuser@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('google-456', $user->google_id);
        $this->assertEquals('admin', $user->role);
        $this->assertNull($user->password);

        // Verify tenant was created
        $this->assertNotNull($user->tenant);
        $this->assertEquals('trial', $user->tenant->status);

        // Verify subscription was created
        $subscription = Subscription::where('tenant_id', $user->tenant->id)->first();
        $this->assertNotNull($subscription);
        $this->assertEquals('active', $subscription->status);

        $this->assertAuthenticatedAs($user);
    }

    public function test_callback_handles_invalid_state_exception(): void
    {
        // Don't fake Socialite - this will cause InvalidStateException
        // when trying to get user without proper state

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
    }

    public function test_callback_route_has_rate_limiting(): void
    {
        // Rate limiting is per IP, so we need to make multiple requests
        // without faking Socialite to trigger the rate limiter
        for ($i = 0; $i < 10; $i++) {
            $this->get(route('auth.google.callback'));
        }

        // 11th request should be rate limited
        $response = $this->get(route('auth.google.callback'));
        $response->assertStatus(429);
    }

    public function test_session_is_regenerated_after_google_login(): void
    {
        $tenant = Tenant::factory()->create();
        User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'session@example.com',
        ]);

        Socialite::fake('google', (new SocialiteUser)->map([
            'id' => 'google-session',
            'name' => 'Session User',
            'email' => 'session@example.com',
            'avatar' => null,
        ]));

        $oldSessionId = session()->getId();

        $this->get(route('auth.google.callback'));

        // Session should be regenerated (new ID)
        $this->assertNotEquals($oldSessionId, session()->getId());
    }

    public function test_redirect_route_is_accessible_only_for_guests(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($user)->get(route('auth.google.redirect'));

        // Should redirect authenticated users away
        $response->assertRedirect(route('dashboard'));
    }

    public function test_callback_route_is_accessible_only_for_guests(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($user)->get(route('auth.google.callback'));

        // Should redirect authenticated users away
        $response->assertRedirect(route('dashboard'));
    }

    public function test_new_user_email_is_verified_after_google_registration(): void
    {
        Plan::factory()->starter()->create();

        Socialite::fake('google', (new SocialiteUser)->map([
            'id' => 'google-verified',
            'name' => 'Verified User',
            'email' => 'verified@example.com',
            'avatar' => null,
        ]));

        $this->get(route('auth.google.callback'));

        $user = User::where('email', 'verified@example.com')->first();
        $this->assertNotNull($user->email_verified_at);
    }
}
