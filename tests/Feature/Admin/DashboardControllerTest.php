<?php

namespace Tests\Feature\Admin;

use App\Models\Alert;
use App\Models\Device;
use App\Models\GatewayInstance;
use App\Models\Invoice;
use App\Models\MessageLog;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $superadmin;

    private User $regularUser;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadmin = User::factory()->superadmin()->create();

        $this->tenant = Tenant::factory()->create(['status' => 'active']);
        $this->regularUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'admin',
        ]);
    }

    // --- Authorization Tests ---

    public function test_guest_cannot_access_admin_dashboard(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_non_superadmin_cannot_access_admin_dashboard(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.dashboard'));

        $response->assertForbidden();
    }

    // --- Dashboard Display Tests ---

    public function test_superadmin_can_view_dashboard(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewIs('admin.dashboard');
        $response->assertViewHas(['stats', 'messageTrend', 'revenueTrend', 'topTenants', 'activeAlerts', 'gateways']);
    }

    public function test_dashboard_shows_correct_messages_today_count(): void
    {
        $device = Device::factory()->create(['tenant_id' => $this->tenant->id]);

        // Messages sent today
        MessageLog::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'device_id' => $device->id,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        // Messages sent yesterday (should not count)
        MessageLog::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'device_id' => $device->id,
            'status' => 'sent',
            'sent_at' => now()->subDay(),
        ]);

        // Pending messages (should not count)
        MessageLog::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'device_id' => $device->id,
            'status' => 'pending',
            'sent_at' => null,
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('stats', function (array $stats) {
            return $stats['messages_today'] === 5;
        });
    }

    public function test_dashboard_shows_correct_active_tenants_count(): void
    {
        // Already have 1 active tenant from setUp
        Tenant::factory()->count(2)->create(['status' => 'active']);
        Tenant::factory()->suspended()->create();
        Tenant::factory()->trial()->create();

        $response = $this->actingAs($this->superadmin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('stats', function (array $stats) {
            return $stats['active_tenants'] === 3;
        });
    }

    public function test_dashboard_shows_correct_connected_devices_count(): void
    {
        Device::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'connected',
        ]);
        Device::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'disconnected',
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('stats', function (array $stats) {
            return $stats['connected_devices'] === 3;
        });
    }

    public function test_dashboard_shows_correct_revenue_this_month(): void
    {
        $plan = Plan::factory()->create();

        // Invoices this month
        Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $plan->id,
            'recorded_by' => $this->superadmin->id,
            'amount' => 150000,
            'paid_at' => now(),
        ]);
        Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $plan->id,
            'recorded_by' => $this->superadmin->id,
            'amount' => 250000,
            'paid_at' => now(),
        ]);

        // Invoice last month (should not count)
        Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $plan->id,
            'recorded_by' => $this->superadmin->id,
            'amount' => 100000,
            'paid_at' => now()->subMonth(),
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('stats', function (array $stats) {
            return (float) $stats['revenue_this_month'] === 400000.0;
        });
    }

    public function test_dashboard_shows_active_alerts(): void
    {
        Alert::factory()->count(3)->create([
            'status' => 'active',
            'severity' => 'warning',
        ]);
        Alert::factory()->create([
            'status' => 'resolved',
            'severity' => 'error',
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('activeAlerts', function ($alerts) {
            return $alerts->count() === 3;
        });
    }

    public function test_dashboard_shows_gateway_instances(): void
    {
        GatewayInstance::factory()->count(2)->create(['status' => 'active']);
        GatewayInstance::factory()->create(['status' => 'error']);

        $response = $this->actingAs($this->superadmin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('gateways', function ($gateways) {
            return $gateways->count() === 3;
        });
    }

    public function test_dashboard_shows_top_tenants_by_message_usage(): void
    {
        $tenantA = Tenant::factory()->create(['name' => 'Tenant A']);
        $tenantB = Tenant::factory()->create(['name' => 'Tenant B']);
        $deviceA = Device::factory()->create(['tenant_id' => $tenantA->id]);
        $deviceB = Device::factory()->create(['tenant_id' => $tenantB->id]);

        MessageLog::factory()->count(10)->create([
            'tenant_id' => $tenantA->id,
            'device_id' => $deviceA->id,
            'status' => 'sent',
            'sent_at' => now(),
        ]);
        MessageLog::factory()->count(5)->create([
            'tenant_id' => $tenantB->id,
            'device_id' => $deviceB->id,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('topTenants', function ($topTenants) {
            return $topTenants->count() === 2
                && $topTenants->first()->name === 'Tenant A'
                && $topTenants->first()->message_count === 10;
        });
    }

    public function test_dashboard_has_30_day_message_trend_data(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('messageTrend', function (array $trend) {
            return count($trend) === 30;
        });
    }

    public function test_dashboard_has_30_day_revenue_trend_data(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('revenueTrend', function (array $trend) {
            return count($trend) === 30;
        });
    }
}
