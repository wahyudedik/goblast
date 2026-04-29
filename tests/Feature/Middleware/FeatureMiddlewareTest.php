<?php

namespace Tests\Feature\Middleware;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class FeatureMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Register test routes with feature middleware
        Route::middleware(['web', 'feature:reminder'])->get('/test-reminder', function () {
            return response()->json(['success' => true]);
        });

        Route::middleware(['web', 'feature:api'])->get('/test-api', function () {
            return response()->json(['success' => true]);
        });

        Route::middleware(['web', 'feature:multi_device'])->get('/test-multi-device', function () {
            return response()->json(['success' => true]);
        });
    }

    public function test_superadmin_can_access_all_features(): void
    {
        $user = User::factory()->create(['role' => 'superadmin']);

        $response = $this->actingAs($user)->get('/test-reminder');
        $response->assertStatus(200);

        $response = $this->actingAs($user)->get('/test-api');
        $response->assertStatus(200);

        $response = $this->actingAs($user)->get('/test-multi-device');
        $response->assertStatus(200);
    }

    public function test_user_with_feature_enabled_can_access(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create([
            'has_reminder' => true,
            'has_api' => false,
            'has_multi_device' => false,
        ]);
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->addDays(25),
        ]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)->get('/test-reminder');
        $response->assertStatus(200);
    }

    public function test_user_without_feature_is_blocked(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create([
            'has_reminder' => false,
            'has_api' => false,
            'has_multi_device' => false,
        ]);
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->addDays(25),
        ]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)->get('/test-reminder');
        $response->assertStatus(403);
        $response->assertSee('Reminder');
        $response->assertSee('not available');
    }

    public function test_user_without_active_subscription_is_blocked(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)->get('/test-reminder');
        $response->assertStatus(403);
        $response->assertSee('No active subscription');
    }

    public function test_user_with_expired_subscription_is_blocked(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create([
            'has_reminder' => true,
        ]);
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'expired',
            'starts_at' => now()->subDays(35),
            'ends_at' => now()->subDays(5),
        ]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)->get('/test-reminder');
        $response->assertStatus(403);
        $response->assertSee('No active subscription');
    }

    public function test_business_plan_has_all_features(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create([
            'slug' => 'business',
            'has_reminder' => true,
            'has_api' => true,
            'has_multi_device' => true,
        ]);
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->addDays(25),
        ]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)->get('/test-reminder');
        $response->assertStatus(200);

        $response = $this->actingAs($user)->get('/test-api');
        $response->assertStatus(200);

        $response = $this->actingAs($user)->get('/test-multi-device');
        $response->assertStatus(200);
    }

    public function test_starter_plan_has_no_features(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create([
            'slug' => 'starter',
            'has_reminder' => false,
            'has_api' => false,
            'has_multi_device' => false,
        ]);
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->addDays(25),
        ]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)->get('/test-reminder');
        $response->assertStatus(403);

        $response = $this->actingAs($user)->get('/test-api');
        $response->assertStatus(403);

        $response = $this->actingAs($user)->get('/test-multi-device');
        $response->assertStatus(403);
    }
}
