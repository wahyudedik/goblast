<?php

namespace Tests\Unit;

use App\Models\ApiToken;
use App\Models\Tenant;
use App\Services\ApiTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTokenServiceTest extends TestCase
{
    use RefreshDatabase;

    private ApiTokenService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ApiTokenService;
    }

    public function test_generate_creates_token_with_hash(): void
    {
        $tenant = Tenant::factory()->create();

        $result = $this->service->generate($tenant, 'Test Token');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('apiToken', $result);
        $this->assertInstanceOf(ApiToken::class, $result['apiToken']);
        $this->assertEquals(64, strlen($result['token']));
        $this->assertEquals('Test Token', $result['apiToken']->name);
        $this->assertEquals($tenant->id, $result['apiToken']->tenant_id);
        $this->assertNull($result['apiToken']->revoked_at);
    }

    public function test_hash_token_uses_sha256(): void
    {
        $token = 'test-token-string';
        $expectedHash = hash('sha256', $token);

        $hash = $this->service->hashToken($token);

        $this->assertEquals($expectedHash, $hash);
        $this->assertEquals(64, strlen($hash)); // SHA-256 produces 64 character hex string
    }

    public function test_validate_returns_api_token_for_valid_token(): void
    {
        $tenant = Tenant::factory()->create();
        $result = $this->service->generate($tenant, 'Valid Token');
        $plainToken = $result['token'];

        $validatedToken = $this->service->validate($plainToken);

        $this->assertNotNull($validatedToken);
        $this->assertInstanceOf(ApiToken::class, $validatedToken);
        $this->assertEquals($result['apiToken']->id, $validatedToken->id);
    }

    public function test_validate_returns_null_for_invalid_token(): void
    {
        $validatedToken = $this->service->validate('invalid-token-string');

        $this->assertNull($validatedToken);
    }

    public function test_validate_returns_null_for_revoked_token(): void
    {
        $tenant = Tenant::factory()->create();
        $result = $this->service->generate($tenant, 'Revoked Token');
        $plainToken = $result['token'];

        // Revoke the token
        $this->service->revoke($result['apiToken']);

        $validatedToken = $this->service->validate($plainToken);

        $this->assertNull($validatedToken);
    }

    public function test_revoke_sets_revoked_at_timestamp(): void
    {
        $tenant = Tenant::factory()->create();
        $result = $this->service->generate($tenant, 'Token to Revoke');
        $apiToken = $result['apiToken'];

        $this->assertNull($apiToken->revoked_at);

        $this->service->revoke($apiToken);

        $apiToken->refresh();
        $this->assertNotNull($apiToken->revoked_at);
    }

    public function test_track_usage_updates_last_used_at(): void
    {
        $tenant = Tenant::factory()->create();
        $result = $this->service->generate($tenant, 'Usage Token');
        $apiToken = $result['apiToken'];

        $this->assertNull($apiToken->last_used_at);

        $this->service->trackUsage($apiToken);

        $apiToken->refresh();
        $this->assertNotNull($apiToken->last_used_at);
    }

    public function test_generated_tokens_are_unique(): void
    {
        $tenant = Tenant::factory()->create();

        $result1 = $this->service->generate($tenant, 'Token 1');
        $result2 = $this->service->generate($tenant, 'Token 2');

        $this->assertNotEquals($result1['token'], $result2['token']);
        $this->assertNotEquals($result1['apiToken']->token_hash, $result2['apiToken']->token_hash);
    }
}
