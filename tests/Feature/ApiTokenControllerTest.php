<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTokenControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create(['status' => 'active']);
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'admin',
        ]);
    }

    public function test_index_displays_api_tokens(): void
    {
        ApiToken::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->user)->get(route('api-tokens.index'));

        $response->assertStatus(200);
        $response->assertViewIs('api-tokens.index');
        $response->assertViewHas('tokens');
    }

    public function test_index_only_shows_tokens_for_current_tenant(): void
    {
        $otherTenant = Tenant::factory()->create(['status' => 'active']);
        ApiToken::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'My Token']);
        ApiToken::factory()->create(['tenant_id' => $otherTenant->id, 'name' => 'Other Token']);

        $response = $this->actingAs($this->user)->get(route('api-tokens.index'));

        $response->assertStatus(200);
        $response->assertSee('My Token');
        $response->assertDontSee('Other Token');
    }

    public function test_create_displays_form(): void
    {
        $response = $this->actingAs($this->user)->get(route('api-tokens.create'));

        $response->assertStatus(200);
        $response->assertViewIs('api-tokens.create');
    }

    public function test_store_creates_new_api_token(): void
    {
        $response = $this->actingAs($this->user)->post(route('api-tokens.store'), [
            'name' => 'Test Token',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('api_tokens', [
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Token',
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)->post(route('api-tokens.store'), [
            'name' => '',
        ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_store_redirects_to_show_with_plain_token(): void
    {
        $response = $this->actingAs($this->user)->post(route('api-tokens.store'), [
            'name' => 'Test Token',
        ]);

        $apiToken = ApiToken::where('name', 'Test Token')->first();

        $response->assertRedirect(route('api-tokens.show', $apiToken));
        $response->assertSessionHas('token');
        $response->assertSessionHas('success');
    }

    public function test_show_displays_api_token_details(): void
    {
        $apiToken = ApiToken::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->user)->get(route('api-tokens.show', $apiToken));

        $response->assertStatus(200);
        $response->assertViewIs('api-tokens.show');
        $response->assertViewHas('apiToken', $apiToken);
        $response->assertViewHas('totalRequests');
    }

    public function test_show_cannot_view_other_tenant_token(): void
    {
        $otherTenant = Tenant::factory()->create(['status' => 'active']);
        $apiToken = ApiToken::factory()->create(['tenant_id' => $otherTenant->id]);

        $response = $this->actingAs($this->user)->get(route('api-tokens.show', $apiToken));

        $response->assertStatus(403);
    }

    public function test_revoke_marks_token_as_revoked(): void
    {
        $apiToken = ApiToken::factory()->create([
            'tenant_id' => $this->tenant->id,
            'revoked_at' => null,
        ]);

        $response = $this->actingAs($this->user)->post(route('api-tokens.revoke', $apiToken));

        $response->assertRedirect(route('api-tokens.index'));
        $response->assertSessionHas('success');

        $apiToken->refresh();
        $this->assertNotNull($apiToken->revoked_at);
    }

    public function test_revoke_cannot_revoke_other_tenant_token(): void
    {
        $otherTenant = Tenant::factory()->create(['status' => 'active']);
        $apiToken = ApiToken::factory()->create([
            'tenant_id' => $otherTenant->id,
            'revoked_at' => null,
        ]);

        $response = $this->actingAs($this->user)->post(route('api-tokens.revoke', $apiToken));

        $response->assertStatus(403);
    }

    public function test_destroy_deletes_api_token(): void
    {
        $apiToken = ApiToken::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->user)->delete(route('api-tokens.destroy', $apiToken));

        $response->assertRedirect(route('api-tokens.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('api_tokens', ['id' => $apiToken->id]);
    }

    public function test_destroy_cannot_delete_other_tenant_token(): void
    {
        $otherTenant = Tenant::factory()->create(['status' => 'active']);
        $apiToken = ApiToken::factory()->create(['tenant_id' => $otherTenant->id]);

        $response = $this->actingAs($this->user)->delete(route('api-tokens.destroy', $apiToken));

        $response->assertStatus(403);
        $this->assertDatabaseHas('api_tokens', ['id' => $apiToken->id]);
    }

    public function test_guest_cannot_access_api_token_pages(): void
    {
        $apiToken = ApiToken::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->get(route('api-tokens.index'))->assertRedirect(route('login'));
        $this->get(route('api-tokens.create'))->assertRedirect(route('login'));
        $this->post(route('api-tokens.store'))->assertRedirect(route('login'));
        $this->get(route('api-tokens.show', $apiToken))->assertRedirect(route('login'));
        $this->post(route('api-tokens.revoke', $apiToken))->assertRedirect(route('login'));
        $this->delete(route('api-tokens.destroy', $apiToken))->assertRedirect(route('login'));
    }
}
