<?php

namespace Tests\Feature\Middleware;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TenantMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Register a test route with tenant middleware
        Route::middleware(['web', 'tenant'])->get('/test-tenant', function (Request $request) {
            return response()->json([
                'success' => true,
                'tenant_id' => $request->get('tenant')?->id,
            ]);
        });
    }

    public function test_superadmin_is_redirected_to_admin_dashboard(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'tenant_id' => null,
        ]);

        $response = $this->actingAs($superadmin)->get('/test-tenant');

        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_user_with_tenant_can_access(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)->get('/test-tenant');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'tenant_id' => $tenant->id,
        ]);
    }

    public function test_user_without_tenant_is_blocked(): void
    {
        $user = User::factory()->create([
            'tenant_id' => null,
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)->get('/test-tenant');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_is_blocked(): void
    {
        $response = $this->get('/test-tenant');

        $response->assertStatus(403);
    }

    public function test_tenant_is_injected_into_request(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Test Tenant']);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'member',
        ]);

        $response = $this->actingAs($user)->get('/test-tenant');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'tenant_id' => $tenant->id,
        ]);
    }
}
