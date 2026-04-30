<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Contracts\BaileysGatewayClientInterface;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeviceCreationRateLimitTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Tenant $tenant;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->for($this->tenant)->create(['role' => 'admin']);

        $plan = Plan::factory()->business()->create();
        Subscription::factory()->for($this->tenant)->for($plan)->active()->create();

        // Mock the gateway client so store requests don't hit a real gateway
        $mock = $this->createMock(BaileysGatewayClientInterface::class);
        $mock->method('getQrCode')->willReturn('mock-qr-code');
        $this->app->instance(BaileysGatewayClientInterface::class, $mock);
    }

    protected function tearDown(): void
    {
        RateLimiter::clear(md5('device-creation'.'tenant:'.$this->tenant->id));
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function it_allows_requests_within_rate_limit_threshold(): void
    {
        $maxAttempts = config('wa-automation.gateway_protection.device_creation.max_attempts', 3);

        for ($i = 0; $i < $maxAttempts; $i++) {
            $response = $this->actingAs($this->user)->post(route('devices.store'), [
                'name' => 'Device '.($i + 1),
            ]);

            $response->assertRedirect();
            $response->assertSessionMissing('rate_limited');
        }
    }

    #[Test]
    public function it_blocks_requests_exceeding_rate_limit_threshold(): void
    {
        $maxAttempts = config('wa-automation.gateway_protection.device_creation.max_attempts', 3);

        // Exhaust the rate limit
        for ($i = 0; $i < $maxAttempts; $i++) {
            $this->actingAs($this->user)->post(route('devices.store'), [
                'name' => 'Device '.($i + 1),
            ]);
        }

        // This request should be rate limited
        $response = $this->actingAs($this->user)->post(route('devices.store'), [
            'name' => 'Blocked Device',
        ]);

        $response->assertRedirect(route('devices.index'));
        $response->assertSessionHas('rate_limited', true);
        $response->assertSessionHas('retry_after');
    }

    #[Test]
    public function it_keys_rate_limit_by_tenant_not_user(): void
    {
        $secondUser = User::factory()->for($this->tenant)->create(['role' => 'member']);
        $maxAttempts = config('wa-automation.gateway_protection.device_creation.max_attempts', 3);

        // First user uses up most of the limit
        for ($i = 0; $i < $maxAttempts - 1; $i++) {
            $this->actingAs($this->user)->post(route('devices.store'), [
                'name' => 'User1 Device '.($i + 1),
            ]);
        }

        // Second user uses the last attempt
        $response = $this->actingAs($secondUser)->post(route('devices.store'), [
            'name' => 'User2 Device',
        ]);
        $response->assertSessionMissing('rate_limited');

        // Second user's next attempt should be blocked (shared tenant limit)
        $response = $this->actingAs($secondUser)->post(route('devices.store'), [
            'name' => 'User2 Blocked Device',
        ]);

        $response->assertRedirect(route('devices.index'));
        $response->assertSessionHas('rate_limited', true);
    }

    #[Test]
    public function it_resets_rate_limit_after_window_expires(): void
    {
        $maxAttempts = config('wa-automation.gateway_protection.device_creation.max_attempts', 3);
        $windowSeconds = config('wa-automation.gateway_protection.device_creation.window_seconds', 300);

        // Exhaust the rate limit
        for ($i = 0; $i < $maxAttempts; $i++) {
            $this->actingAs($this->user)->post(route('devices.store'), [
                'name' => 'Device '.($i + 1),
            ]);
        }

        // Verify rate limited
        $response = $this->actingAs($this->user)->post(route('devices.store'), [
            'name' => 'Blocked Device',
        ]);
        $response->assertSessionHas('rate_limited', true);

        // Travel past the window
        Carbon::setTestNow(now()->addSeconds($windowSeconds + 1));

        // Should be allowed again
        $response = $this->actingAs($this->user)->post(route('devices.store'), [
            'name' => 'Allowed After Reset',
        ]);

        $response->assertSessionMissing('rate_limited');
    }

    #[Test]
    public function it_redirects_with_error_when_rate_limited_on_direct_route_access(): void
    {
        $maxAttempts = config('wa-automation.gateway_protection.device_creation.max_attempts', 3);

        // Exhaust the rate limit via store
        for ($i = 0; $i < $maxAttempts; $i++) {
            $this->actingAs($this->user)->post(route('devices.store'), [
                'name' => 'Device '.($i + 1),
            ]);
        }

        // Direct POST to store should redirect with rate_limited flash
        $response = $this->actingAs($this->user)->post(route('devices.store'), [
            'name' => 'Direct Access Device',
        ]);

        $response->assertRedirect(route('devices.index'));
        $response->assertSessionHas('rate_limited', true);
        $response->assertSessionHas('retry_after');
    }

    #[Test]
    public function it_returns_correct_json_from_rate_limit_status_endpoint(): void
    {
        // Before any attempts, should not be limited
        $response = $this->actingAs($this->user)->getJson(route('devices.rate-limit-status'));

        $response->assertOk();
        $response->assertJson([
            'is_limited' => false,
            'remaining_attempts' => config('wa-automation.gateway_protection.device_creation.max_attempts', 3),
            'retry_after' => 0,
        ]);
    }

    #[Test]
    public function it_returns_limited_status_from_rate_limit_status_endpoint_after_exceeding_limit(): void
    {
        $maxAttempts = config('wa-automation.gateway_protection.device_creation.max_attempts', 3);

        // Exhaust the rate limit
        for ($i = 0; $i < $maxAttempts; $i++) {
            $this->actingAs($this->user)->post(route('devices.store'), [
                'name' => 'Device '.($i + 1),
            ]);
        }

        // Check status endpoint
        $response = $this->actingAs($this->user)->getJson(route('devices.rate-limit-status'));

        $response->assertOk();
        $response->assertJson([
            'is_limited' => true,
            'remaining_attempts' => 0,
        ]);

        $data = $response->json();
        $this->assertGreaterThan(0, $data['retry_after']);
    }
}
