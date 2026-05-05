<?php

namespace Tests\Feature\Integration;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Contracts\GatewayClientInterface;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeviceCreationFlowIntegrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Tenant $tenant;

    private User $user;

    private int $maxAttempts;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->for($this->tenant)->create(['role' => 'admin']);

        $plan = Plan::factory()->business()->create();
        Subscription::factory()->for($this->tenant)->for($plan)->active()->create();

        $this->maxAttempts = config('wa-automation.gateway_protection.device_creation.max_attempts', 3);

        // Mock the gateway client so store requests don't hit a real gateway
        $mock = $this->createMock(GatewayClientInterface::class);
        $mock->method('getQrCode')->willReturn('mock-qr-code');
        $this->app->instance(GatewayClientInterface::class, $mock);
    }

    protected function tearDown(): void
    {
        RateLimiter::clear(md5('device-creation'.'tenant:'.$this->tenant->id));
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function it_allows_device_creation_up_to_limit_then_rate_limits_with_countdown(): void
    {
        // Create devices up to the configured limit — all should succeed
        for ($i = 0; $i < $this->maxAttempts; $i++) {
            $response = $this->actingAs($this->user)->post(route('devices.store'), [
                'name' => 'Device '.($i + 1),
            ]);

            $response->assertSessionMissing('rate_limited');
            $this->assertDatabaseHas('devices', ['name' => 'Device '.($i + 1)]);
        }

        // The next request should be rate limited
        $response = $this->actingAs($this->user)->post(route('devices.store'), [
            'name' => 'Blocked Device',
        ]);

        $response->assertRedirect(route('devices.index'));
        $response->assertSessionHas('rate_limited', true);
        $response->assertSessionHas('retry_after');

        $retryAfter = session('retry_after');
        $this->assertIsInt($retryAfter);
        $this->assertGreaterThan(0, $retryAfter);

        // Verify the blocked device was NOT created
        $this->assertDatabaseMissing('devices', ['name' => 'Blocked Device']);
    }

    #[Test]
    public function it_returns_accurate_countdown_data_from_rate_limit_status_endpoint(): void
    {
        // Exhaust the rate limit
        for ($i = 0; $i < $this->maxAttempts; $i++) {
            $this->actingAs($this->user)->post(route('devices.store'), [
                'name' => 'Device '.($i + 1),
            ]);
        }

        // Check the AJAX status endpoint
        $response = $this->actingAs($this->user)->getJson(route('devices.rate-limit-status'));

        $response->assertOk();
        $response->assertJsonStructure(['is_limited', 'remaining_attempts', 'retry_after']);

        $data = $response->json();
        $this->assertTrue($data['is_limited']);
        $this->assertEquals(0, $data['remaining_attempts']);
        $this->assertGreaterThan(0, $data['retry_after']);
    }

    #[Test]
    public function it_resumes_device_creation_after_rate_limit_window_expires(): void
    {
        $windowSeconds = config('wa-automation.gateway_protection.device_creation.window_seconds', 300);

        // Exhaust the rate limit
        for ($i = 0; $i < $this->maxAttempts; $i++) {
            $this->actingAs($this->user)->post(route('devices.store'), [
                'name' => 'Device '.($i + 1),
            ]);
        }

        // Confirm rate limited
        $response = $this->actingAs($this->user)->post(route('devices.store'), [
            'name' => 'Blocked Device',
        ]);
        $response->assertSessionHas('rate_limited', true);

        // Travel past the rate limit window
        Carbon::setTestNow(now()->addSeconds($windowSeconds + 1));

        // Should be allowed again
        $response = $this->actingAs($this->user)->post(route('devices.store'), [
            'name' => 'Allowed After Window',
        ]);

        $response->assertSessionMissing('rate_limited');
        $this->assertDatabaseHas('devices', ['name' => 'Allowed After Window']);

        // Status endpoint should also reflect the reset
        $response = $this->actingAs($this->user)->getJson(route('devices.rate-limit-status'));
        $data = $response->json();
        $this->assertFalse($data['is_limited']);
    }

    #[Test]
    public function it_shares_rate_limit_across_tenant_users_in_full_flow(): void
    {
        $secondUser = User::factory()->for($this->tenant)->create(['role' => 'member']);

        // First user creates devices up to limit - 1
        for ($i = 0; $i < $this->maxAttempts - 1; $i++) {
            $this->actingAs($this->user)->post(route('devices.store'), [
                'name' => 'Admin Device '.($i + 1),
            ]);
        }

        // Second user uses the last attempt
        $response = $this->actingAs($secondUser)->post(route('devices.store'), [
            'name' => 'Member Device',
        ]);
        $response->assertSessionMissing('rate_limited');

        // Both users should now be rate limited
        $response = $this->actingAs($this->user)->post(route('devices.store'), [
            'name' => 'Admin Blocked',
        ]);
        $response->assertSessionHas('rate_limited', true);

        $response = $this->actingAs($secondUser)->post(route('devices.store'), [
            'name' => 'Member Blocked',
        ]);
        $response->assertSessionHas('rate_limited', true);

        // Both users see the same limited status via AJAX
        $adminStatus = $this->actingAs($this->user)->getJson(route('devices.rate-limit-status'))->json();
        $memberStatus = $this->actingAs($secondUser)->getJson(route('devices.rate-limit-status'))->json();

        $this->assertTrue($adminStatus['is_limited']);
        $this->assertTrue($memberStatus['is_limited']);
    }

    #[Test]
    public function it_blocks_create_route_access_when_rate_limited(): void
    {
        // Exhaust the rate limit
        for ($i = 0; $i < $this->maxAttempts; $i++) {
            $this->actingAs($this->user)->post(route('devices.store'), [
                'name' => 'Device '.($i + 1),
            ]);
        }

        // Attempting to access the create form should redirect with error
        $response = $this->actingAs($this->user)->get(route('devices.create'));

        $response->assertRedirect(route('devices.index'));
        $response->assertSessionHas('rate_limited', true);
        $response->assertSessionHas('error');
    }
}
