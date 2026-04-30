<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Contracts\BaileysGatewayClientInterface;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeviceCreationRateLimitBypassTest extends TestCase
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

        $mock = $this->createMock(BaileysGatewayClientInterface::class);
        $mock->method('getQrCode')->willReturn('mock-qr-code');
        $this->app->instance(BaileysGatewayClientInterface::class, $mock);
    }

    protected function tearDown(): void
    {
        RateLimiter::clear(md5('device-creation'.'tenant:'.$this->tenant->id));

        parent::tearDown();
    }

    #[Test]
    public function it_redirects_with_error_when_accessing_create_route_while_rate_limited(): void
    {
        $maxAttempts = config('wa-automation.gateway_protection.device_creation.max_attempts', 3);

        // Exhaust the rate limit via store
        for ($i = 0; $i < $maxAttempts; $i++) {
            $this->actingAs($this->user)->post(route('devices.store'), [
                'name' => 'Device '.($i + 1),
            ]);
        }

        // Attempt to access the create route directly (UI bypass)
        $response = $this->actingAs($this->user)->get(route('devices.create'));

        $response->assertRedirect(route('devices.index'));
        $response->assertSessionHas('rate_limited', true);
        $response->assertSessionHas('retry_after');
        $response->assertSessionHas('error');
    }

    #[Test]
    public function it_allows_create_route_access_when_not_rate_limited(): void
    {
        $response = $this->actingAs($this->user)->get(route('devices.create'));

        $response->assertOk();
        $response->assertViewIs('devices.create');
    }
}
