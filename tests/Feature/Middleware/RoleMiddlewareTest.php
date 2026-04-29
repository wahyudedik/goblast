<?php

namespace Tests\Feature\Middleware;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Register test routes with role middleware
        Route::middleware(['web', 'role:superadmin'])->get('/test-superadmin', function () {
            return response()->json(['success' => true]);
        });

        Route::middleware(['web', 'role:admin,superadmin'])->get('/test-admin', function () {
            return response()->json(['success' => true]);
        });

        Route::middleware(['web', 'role:member,admin,superadmin'])->get('/test-member', function () {
            return response()->json(['success' => true]);
        });
    }

    public function test_superadmin_can_access_superadmin_route(): void
    {
        $user = User::factory()->create(['role' => 'superadmin']);

        $response = $this->actingAs($user)->get('/test-superadmin');

        $response->assertStatus(200);
    }

    public function test_admin_cannot_access_superadmin_route(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)->get('/test-superadmin');

        $response->assertStatus(403);
    }

    public function test_admin_can_access_admin_route(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)->get('/test-admin');

        $response->assertStatus(200);
    }

    public function test_superadmin_can_access_admin_route(): void
    {
        $user = User::factory()->create(['role' => 'superadmin']);

        $response = $this->actingAs($user)->get('/test-admin');

        $response->assertStatus(200);
    }

    public function test_member_cannot_access_admin_route(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'member',
        ]);

        $response = $this->actingAs($user)->get('/test-admin');

        $response->assertStatus(403);
    }

    public function test_member_can_access_member_route(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'member',
        ]);

        $response = $this->actingAs($user)->get('/test-member');

        $response->assertStatus(200);
    }

    public function test_unauthenticated_user_is_blocked(): void
    {
        $response = $this->get('/test-member');

        $response->assertStatus(403);
    }
}
