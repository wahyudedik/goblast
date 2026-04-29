<?php

namespace Tests\Feature\Admin;

use App\Models\GatewayInstance;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Contracts\BaileysGatewayClientInterface;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class GatewayControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $superadmin;

    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadmin = User::factory()->superadmin()->create();

        $tenant = Tenant::factory()->create(['status' => 'active']);
        $this->regularUser = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
        ]);
    }

    // --- Authorization Tests ---

    public function test_non_superadmin_cannot_access_gateway_index(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.gateways.index'));

        $response->assertForbidden();
    }

    public function test_guest_cannot_access_gateway_index(): void
    {
        $response = $this->get(route('admin.gateways.index'));

        $response->assertRedirect(route('login'));
    }

    // --- Index Tests ---

    public function test_superadmin_can_view_gateway_index(): void
    {
        GatewayInstance::factory()->count(3)->create();

        $response = $this->actingAs($this->superadmin)->get(route('admin.gateways.index'));

        $response->assertOk();
        $response->assertViewIs('admin.gateways.index');
        $response->assertViewHas('gateways');
    }

    public function test_gateway_index_displays_all_columns(): void
    {
        $gateway = GatewayInstance::factory()->create([
            'name' => 'Test Gateway',
            'base_url' => 'https://gw.example.com',
            'status' => 'active',
            'last_checked_at' => now(),
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.gateways.index'));

        $response->assertOk();
        $response->assertSee('Test Gateway');
        $response->assertSee('https://gw.example.com');
    }

    public function test_gateway_index_shows_empty_state(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('admin.gateways.index'));

        $response->assertOk();
        $response->assertSee('Tidak ada gateway');
    }

    public function test_gateway_index_shows_status_badges(): void
    {
        GatewayInstance::factory()->create(['status' => 'active']);
        GatewayInstance::factory()->inactive()->create();
        GatewayInstance::factory()->error()->create();

        $response = $this->actingAs($this->superadmin)->get(route('admin.gateways.index'));

        $response->assertOk();
        $response->assertSee('Active');
        $response->assertSee('Inactive');
        $response->assertSee('Error');
    }

    // --- Show Tests ---

    public function test_superadmin_can_view_gateway_details(): void
    {
        $gateway = GatewayInstance::factory()->create(['name' => 'My Gateway']);

        $response = $this->actingAs($this->superadmin)->get(route('admin.gateways.show', $gateway));

        $response->assertOk();
        $response->assertViewIs('admin.gateways.show');
        $response->assertViewHas('gateway');
        $response->assertSee('My Gateway');
    }

    public function test_show_displays_last_error_when_present(): void
    {
        $gateway = GatewayInstance::factory()->error()->create([
            'last_error' => 'Connection refused to gateway',
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.gateways.show', $gateway));

        $response->assertOk();
        $response->assertSee('Connection refused to gateway');
        $response->assertSee('Error Terakhir');
    }

    public function test_show_hides_error_section_when_no_error(): void
    {
        $gateway = GatewayInstance::factory()->create(['last_error' => null]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.gateways.show', $gateway));

        $response->assertOk();
        $response->assertDontSee('Error Terakhir');
    }

    public function test_non_superadmin_cannot_view_gateway_details(): void
    {
        $gateway = GatewayInstance::factory()->create();

        $response = $this->actingAs($this->regularUser)->get(route('admin.gateways.show', $gateway));

        $response->assertForbidden();
    }

    // --- Restart Tests ---

    public function test_superadmin_can_restart_gateway(): void
    {
        $gateway = GatewayInstance::factory()->error()->create();

        $mock = $this->mock(BaileysGatewayClientInterface::class);
        $mock->shouldReceive('restartInstance')
            ->once()
            ->with((string) $gateway->id);

        $response = $this->actingAs($this->superadmin)->post(route('admin.gateways.restart', $gateway));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $gateway->refresh();
        $this->assertEquals('active', $gateway->status);
        $this->assertNull($gateway->last_error);
        $this->assertNotNull($gateway->last_checked_at);
    }

    public function test_restart_handles_gateway_failure(): void
    {
        $gateway = GatewayInstance::factory()->create(['status' => 'active']);

        $mock = $this->mock(BaileysGatewayClientInterface::class);
        $mock->shouldReceive('restartInstance')
            ->once()
            ->andThrow(new \Exception('Connection timeout'));

        $response = $this->actingAs($this->superadmin)->post(route('admin.gateways.restart', $gateway));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $gateway->refresh();
        $this->assertEquals('error', $gateway->status);
        $this->assertEquals('Connection timeout', $gateway->last_error);
    }

    public function test_non_superadmin_cannot_restart_gateway(): void
    {
        $gateway = GatewayInstance::factory()->create();

        $response = $this->actingAs($this->regularUser)->post(route('admin.gateways.restart', $gateway));

        $response->assertForbidden();
    }

    // --- Delete Tests ---

    public function test_superadmin_can_delete_gateway(): void
    {
        $gateway = GatewayInstance::factory()->create();

        $response = $this->actingAs($this->superadmin)->delete(route('admin.gateways.destroy', $gateway));

        $response->assertRedirect(route('admin.gateways.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('gateway_instances', ['id' => $gateway->id]);
    }

    public function test_non_superadmin_cannot_delete_gateway(): void
    {
        $gateway = GatewayInstance::factory()->create();

        $response = $this->actingAs($this->regularUser)->delete(route('admin.gateways.destroy', $gateway));

        $response->assertForbidden();
        $this->assertDatabaseHas('gateway_instances', ['id' => $gateway->id]);
    }
}
