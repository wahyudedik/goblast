<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiDocPageTest extends TestCase
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

    public function test_authenticated_tenant_user_can_access_api_docs_page(): void
    {
        $response = $this->actingAs($this->user)->get(route('api-docs.index'));

        $response->assertStatus(200);
        $response->assertViewIs('api-docs.index');
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get(route('api-docs.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_page_displays_dynamic_base_url_from_config(): void
    {
        config(['app.url' => 'https://example-test.com']);

        $response = $this->actingAs($this->user)->get(route('api-docs.index'));

        $response->assertStatus(200);
        $response->assertSee('https://example-test.com/api/v1');
    }

    public function test_page_displays_table_of_contents_with_anchor_links(): void
    {
        $response = $this->actingAs($this->user)->get(route('api-docs.index'));

        $response->assertStatus(200);
        $response->assertSee('href="#autentikasi"', false);
        $response->assertSee('href="#send-message"', false);
        $response->assertSee('href="#send-bulk"', false);
        $response->assertSee('href="#message-status"', false);
        $response->assertSee('href="#error-handling"', false);
        $response->assertSee('href="#rate-limiting"', false);
        $response->assertSee('href="#contoh-kode"', false);
    }

    public function test_page_displays_authentication_section(): void
    {
        $response = $this->actingAs($this->user)->get(route('api-docs.index'));

        $response->assertStatus(200);
        $response->assertSee('Authorization: Bearer {your_api_token}');
        $response->assertSee(route('api-tokens.index'), false);
        $response->assertSee('Peringatan Keamanan');
        $response->assertSee('Jaga kerahasiaan token API Anda');
    }

    public function test_page_displays_send_message_endpoint(): void
    {
        $response = $this->actingAs($this->user)->get(route('api-docs.index'));

        $response->assertStatus(200);
        $response->assertSee('POST');
        $response->assertSee('/api/v1/send-message');
        $response->assertSee('device_id');
        $response->assertSeeText('to');
        $response->assertSee('message');
        $response->assertSee('template_id');
        $response->assertSee('4096');
    }

    public function test_page_displays_send_bulk_endpoint(): void
    {
        $response = $this->actingAs($this->user)->get(route('api-docs.index'));

        $response->assertStatus(200);
        $response->assertSee('/api/v1/send-bulk');
        $response->assertSee('recipients');
        $response->assertSee('10.000');
    }

    public function test_page_displays_message_status_endpoint(): void
    {
        $response = $this->actingAs($this->user)->get(route('api-docs.index'));

        $response->assertStatus(200);
        $response->assertSee('GET');
        $response->assertSee('/api/v1/message-status/{jobId}');
        $response->assertSee('pending');
        $response->assertSee('sent');
        $response->assertSee('failed');
        $response->assertSee('cancelled');
        $response->assertSee('retrying');
    }

    public function test_page_displays_error_handling_section(): void
    {
        $response = $this->actingAs($this->user)->get(route('api-docs.index'));

        $response->assertStatus(200);
        $response->assertSee('Penanganan Error');
        $response->assertSeeText('200');
        $response->assertSeeText('202');
        $response->assertSeeText('401');
        $response->assertSeeText('403');
        $response->assertSeeText('404');
        $response->assertSeeText('422');
        $response->assertSeeText('429');
    }

    public function test_page_displays_code_examples(): void
    {
        $response = $this->actingAs($this->user)->get(route('api-docs.index'));

        $response->assertStatus(200);
        $response->assertSee('PHP (Guzzle)');
        $response->assertSee('JavaScript (Fetch)');
        $response->assertSee('cURL');
        $response->assertSee('GuzzleHttp');
        $response->assertSee('fetch(', false);
    }

    public function test_page_displays_rate_limiting_section(): void
    {
        $response = $this->actingAs($this->user)->get(route('api-docs.index'));

        $response->assertStatus(200);
        $response->assertSee('Rate Limiting');
        $response->assertSee('60 request', false);
        $response->assertSee('X-RateLimit-Limit');
        $response->assertSee('X-RateLimit-Remaining');
        $response->assertSee('X-RateLimit-Reset');
        $response->assertSeeText('429');
    }

    public function test_sidebar_contains_api_docs_link(): void
    {
        $response = $this->actingAs($this->user)->get(route('api-docs.index'));

        $response->assertStatus(200);
        $response->assertSee('API Docs');
    }

    public function test_api_tokens_page_contains_docs_link(): void
    {
        $response = $this->actingAs($this->user)->get(route('api-tokens.index'));

        $response->assertStatus(200);
        $response->assertSee('Lihat Dokumentasi API');
        $response->assertSee(route('api-docs.index'), false);
    }
}
