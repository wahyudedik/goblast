<?php

namespace Tests\Feature\Admin;

use App\Models\Alert;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AlertControllerTest extends TestCase
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

    public function test_non_superadmin_cannot_access_alert_index(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.alerts.index'));

        $response->assertForbidden();
    }

    public function test_guest_cannot_access_alert_index(): void
    {
        $response = $this->get(route('admin.alerts.index'));

        $response->assertRedirect(route('login'));
    }

    // --- Index Tests ---

    public function test_superadmin_can_view_alert_index(): void
    {
        Alert::factory()->count(3)->create();

        $response = $this->actingAs($this->superadmin)->get(route('admin.alerts.index'));

        $response->assertOk();
        $response->assertViewIs('admin.alerts.index');
        $response->assertViewHas('alerts');
    }

    public function test_alert_index_displays_all_columns(): void
    {
        $alert = Alert::factory()->create([
            'type' => 'gateway.down',
            'severity' => 'critical',
            'message' => 'Gateway instance is unreachable',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.alerts.index'));

        $response->assertOk();
        $response->assertSee('gateway.down');
        $response->assertSee('Gateway instance is unreachable');
        $response->assertSee('Critical');
        $response->assertSee('Active');
    }

    public function test_alert_index_shows_empty_state(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('admin.alerts.index'));

        $response->assertOk();
        $response->assertSee('Tidak ada alert');
    }

    public function test_alert_index_filters_by_status(): void
    {
        Alert::factory()->create(['status' => 'active', 'message' => 'Active alert message']);
        Alert::factory()->resolved()->create(['message' => 'Resolved alert message']);

        $response = $this->actingAs($this->superadmin)->get(route('admin.alerts.index', ['status' => 'active']));

        $response->assertOk();
        $response->assertSee('Active alert message');
        $response->assertDontSee('Resolved alert message');
    }

    public function test_alert_index_filters_by_severity(): void
    {
        Alert::factory()->create(['severity' => 'critical', 'message' => 'Critical alert']);
        Alert::factory()->create(['severity' => 'warning', 'message' => 'Warning alert']);

        $response = $this->actingAs($this->superadmin)->get(route('admin.alerts.index', ['severity' => 'critical']));

        $response->assertOk();
        $response->assertSee('Critical alert');
        $response->assertDontSee('Warning alert');
    }

    public function test_alert_index_filters_by_type(): void
    {
        Alert::factory()->create(['type' => 'gateway.down', 'message' => 'Gateway down alert']);
        Alert::factory()->create(['type' => 'quota.90pct', 'message' => 'Quota alert']);

        $response = $this->actingAs($this->superadmin)->get(route('admin.alerts.index', ['type' => 'gateway.down']));

        $response->assertOk();
        $response->assertSee('Gateway down alert');
        $response->assertDontSee('Quota alert');
    }

    public function test_alert_index_filters_by_date_range(): void
    {
        Alert::factory()->create([
            'message' => 'Old alert',
            'created_at' => now()->subDays(30),
        ]);
        Alert::factory()->create([
            'message' => 'Recent alert',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.alerts.index', [
            'date_from' => now()->subDay()->format('Y-m-d'),
            'date_to' => now()->addDay()->format('Y-m-d'),
        ]));

        $response->assertOk();
        $response->assertSee('Recent alert');
        $response->assertDontSee('Old alert');
    }

    // --- Show Tests ---

    public function test_superadmin_can_view_alert_details(): void
    {
        $alert = Alert::factory()->create([
            'type' => 'gateway.down',
            'message' => 'Gateway is unreachable',
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.alerts.show', $alert));

        $response->assertOk();
        $response->assertViewIs('admin.alerts.show');
        $response->assertViewHas('alert');
        $response->assertSee('gateway.down');
        $response->assertSee('Gateway is unreachable');
    }

    public function test_show_displays_context_when_present(): void
    {
        $alert = Alert::factory()->create([
            'context' => ['instance_id' => 42, 'url' => 'https://gw.example.com'],
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.alerts.show', $alert));

        $response->assertOk();
        $response->assertSee('Context Information');
        $response->assertSee('instance_id');
    }

    public function test_show_hides_context_section_when_null(): void
    {
        $alert = Alert::factory()->create(['context' => null]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.alerts.show', $alert));

        $response->assertOk();
        // The context section heading should not appear when context is null
        $response->assertDontSee('<h3 class="text-base font-semibold text-gray-900">Context Information</h3>', false);
    }

    public function test_show_displays_resolved_info(): void
    {
        $alert = Alert::factory()->resolved()->create([
            'resolved_by' => $this->superadmin->id,
            'resolved_at' => now(),
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.alerts.show', $alert));

        $response->assertOk();
        $response->assertSee('Resolved');
        $response->assertSee($this->superadmin->name);
    }

    public function test_non_superadmin_cannot_view_alert_details(): void
    {
        $alert = Alert::factory()->create();

        $response = $this->actingAs($this->regularUser)->get(route('admin.alerts.show', $alert));

        $response->assertForbidden();
    }

    // --- Resolve Tests ---

    public function test_superadmin_can_resolve_alert(): void
    {
        $alert = Alert::factory()->create(['status' => 'active']);

        $response = $this->actingAs($this->superadmin)->post(route('admin.alerts.resolve', $alert));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $alert->refresh();
        $this->assertEquals('resolved', $alert->status);
        $this->assertEquals($this->superadmin->id, $alert->resolved_by);
        $this->assertNotNull($alert->resolved_at);
    }

    public function test_resolve_already_resolved_alert_returns_error(): void
    {
        $alert = Alert::factory()->resolved()->create([
            'resolved_by' => $this->superadmin->id,
        ]);

        $response = $this->actingAs($this->superadmin)->post(route('admin.alerts.resolve', $alert));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_non_superadmin_cannot_resolve_alert(): void
    {
        $alert = Alert::factory()->create(['status' => 'active']);

        $response = $this->actingAs($this->regularUser)->post(route('admin.alerts.resolve', $alert));

        $response->assertForbidden();
    }

    // --- Delete Tests ---

    public function test_superadmin_can_delete_alert(): void
    {
        $alert = Alert::factory()->create();

        $response = $this->actingAs($this->superadmin)->delete(route('admin.alerts.destroy', $alert));

        $response->assertRedirect(route('admin.alerts.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('alerts', ['id' => $alert->id]);
    }

    public function test_non_superadmin_cannot_delete_alert(): void
    {
        $alert = Alert::factory()->create();

        $response = $this->actingAs($this->regularUser)->delete(route('admin.alerts.destroy', $alert));

        $response->assertForbidden();
        $this->assertDatabaseHas('alerts', ['id' => $alert->id]);
    }
}
