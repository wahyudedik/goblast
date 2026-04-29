<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Services\ApiTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApiTokenMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test route that uses the api.token middleware
        Route::get('/api/test-endpoint', function () {
            return response()->json([
                'success' => true,
                'tenant_id' => request()->attributes->get('tenant')->id,
            ]);
        })->middleware('api.token');
    }

    public function test_middleware_returns_401_when_no_authorization_header(): void
    {
        $response = $this->getJson('/api/test-endpoint');

        $response->assertStatus(401);
        $response->assertJson([
            'error' => 'Unauthorized',
            'message' => 'Token tidak valid atau tidak ditemukan',
        ]);
    }

    public function test_middleware_returns_401_when_authorization_header_missing_bearer_prefix(): void
    {
        $response = $this->getJson('/api/test-endpoint', [
            'Authorization' => 'InvalidFormat token123',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'error' => 'Unauthorized',
            'message' => 'Token tidak valid atau tidak ditemukan',
        ]);
    }

    public function test_middleware_returns_401_for_invalid_token(): void
    {
        $response = $this->getJson('/api/test-endpoint', [
            'Authorization' => 'Bearer invalid-token-string',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'error' => 'Unauthorized',
            'message' => 'Token tidak valid atau tidak ditemukan',
        ]);
    }

    public function test_middleware_returns_401_for_revoked_token(): void
    {
        $tenant = Tenant::factory()->create();
        $service = new ApiTokenService;
        $result = $service->generate($tenant, 'Test Token');
        $token = $result['token'];

        // Revoke the token
        $service->revoke($result['apiToken']);

        $response = $this->getJson('/api/test-endpoint', [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'error' => 'Unauthorized',
            'message' => 'Token tidak valid atau tidak ditemukan',
        ]);
    }

    public function test_middleware_allows_request_with_valid_token(): void
    {
        $tenant = Tenant::factory()->create();
        $service = new ApiTokenService;
        $result = $service->generate($tenant, 'Valid Token');
        $token = $result['token'];

        $response = $this->getJson('/api/test-endpoint', [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'tenant_id' => $tenant->id,
        ]);
    }

    public function test_middleware_injects_tenant_context_into_request(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Test Tenant']);
        $service = new ApiTokenService;
        $result = $service->generate($tenant, 'Context Token');
        $token = $result['token'];

        Route::get('/api/test-context', function () {
            $tenant = request()->attributes->get('tenant');
            $apiToken = request()->attributes->get('api_token');

            return response()->json([
                'tenant_name' => $tenant->name,
                'api_token_name' => $apiToken->name,
            ]);
        })->middleware('api.token');

        $response = $this->getJson('/api/test-context', [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'tenant_name' => 'Test Tenant',
            'api_token_name' => 'Context Token',
        ]);
    }

    public function test_middleware_tracks_token_usage(): void
    {
        $tenant = Tenant::factory()->create();
        $service = new ApiTokenService;
        $result = $service->generate($tenant, 'Usage Token');
        $token = $result['token'];
        $apiToken = $result['apiToken'];

        $this->assertNull($apiToken->last_used_at);

        $this->getJson('/api/test-endpoint', [
            'Authorization' => "Bearer {$token}",
        ]);

        $apiToken->refresh();
        $this->assertNotNull($apiToken->last_used_at);
    }
}
