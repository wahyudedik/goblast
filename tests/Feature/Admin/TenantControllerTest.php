<?php

namespace Tests\Feature\Admin;

use App\Models\Device;
use App\Models\MessageLog;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TenantControllerTest extends TestCase
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

    public function test_non_superadmin_cannot_access_tenant_index(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.tenants.index'));

        $response->assertForbidden();
    }

    public function test_guest_cannot_access_tenant_index(): void
    {
        $response = $this->get(route('admin.tenants.index'));

        $response->assertRedirect(route('login'));
    }

    // --- Index Tests ---

    public function test_superadmin_can_view_tenant_index(): void
    {
        Tenant::factory()->count(3)->create();

        $response = $this->actingAs($this->superadmin)->get(route('admin.tenants.index'));

        $response->assertOk();
        $response->assertViewIs('admin.tenants.index');
        $response->assertViewHas('tenants');
    }

    public function test_can_filter_tenants_by_status(): void
    {
        Tenant::factory()->create(['status' => 'trial']);
        Tenant::factory()->suspended()->create();

        $response = $this->actingAs($this->superadmin)->get(route('admin.tenants.index', ['status' => 'trial']));

        $response->assertOk();
        $response->assertViewHas('tenants', function ($tenants) {
            return $tenants->every(fn ($t) => $t->status === 'trial');
        });
    }

    public function test_can_filter_tenants_by_plan(): void
    {
        $plan = Plan::factory()->create(['name' => 'Pro', 'slug' => 'pro']);
        $tenantWithPlan = Tenant::factory()->create();
        Subscription::factory()->create([
            'tenant_id' => $tenantWithPlan->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.tenants.index', ['plan' => $plan->id]));

        $response->assertOk();
        $response->assertViewHas('tenants', function ($tenants) use ($tenantWithPlan) {
            return $tenants->contains('id', $tenantWithPlan->id);
        });
    }

    public function test_can_filter_tenants_by_date_range(): void
    {
        $oldTenant = Tenant::factory()->create(['created_at' => now()->subMonths(3)]);
        $newTenant = Tenant::factory()->create(['created_at' => now()]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.tenants.index', [
            'date_from' => now()->subDay()->format('Y-m-d'),
            'date_to' => now()->addDay()->format('Y-m-d'),
        ]));

        $response->assertOk();
        $response->assertViewHas('tenants', function ($tenants) use ($newTenant, $oldTenant) {
            return $tenants->contains('id', $newTenant->id)
                && ! $tenants->contains('id', $oldTenant->id);
        });
    }

    // --- Create Tests ---

    public function test_superadmin_can_view_create_form(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('admin.tenants.create'));

        $response->assertOk();
        $response->assertViewIs('admin.tenants.create');
    }

    public function test_superadmin_can_create_tenant(): void
    {
        $data = [
            'name' => 'New Tenant Corp',
            'email' => 'newtenant@example.com',
            'phone' => '6281234567890',
        ];

        $response = $this->actingAs($this->superadmin)->post(route('admin.tenants.store'), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('tenants', [
            'name' => 'New Tenant Corp',
            'email' => 'newtenant@example.com',
            'phone' => '6281234567890',
            'status' => 'trial',
        ]);

        $tenant = Tenant::where('email', 'newtenant@example.com')->first();
        $this->assertNotNull($tenant->trial_ends_at);
        $this->assertTrue($tenant->trial_ends_at->isFuture());
    }

    public function test_create_tenant_requires_name_and_email(): void
    {
        $response = $this->actingAs($this->superadmin)->post(route('admin.tenants.store'), []);

        $response->assertSessionHasErrors(['name', 'email']);
    }

    public function test_create_tenant_requires_unique_email(): void
    {
        $data = [
            'name' => 'Duplicate Tenant',
            'email' => $this->tenant->email,
        ];

        $response = $this->actingAs($this->superadmin)->post(route('admin.tenants.store'), $data);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_non_superadmin_cannot_create_tenant(): void
    {
        $response = $this->actingAs($this->regularUser)->post(route('admin.tenants.store'), [
            'name' => 'Test',
            'email' => 'test@example.com',
        ]);

        $response->assertForbidden();
    }

    // --- Show Tests ---

    public function test_superadmin_can_view_tenant_details(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('admin.tenants.show', $this->tenant));

        $response->assertOk();
        $response->assertViewIs('admin.tenants.show');
        $response->assertViewHas('tenant');
        $response->assertSee($this->tenant->name);
    }

    public function test_show_displays_subscription_info(): void
    {
        $plan = Plan::factory()->create(['name' => 'Business']);
        Subscription::factory()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.tenants.show', $this->tenant));

        $response->assertOk();
        $response->assertSee('Business');
    }

    // --- Edit Tests ---

    public function test_superadmin_can_view_edit_form(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('admin.tenants.edit', $this->tenant));

        $response->assertOk();
        $response->assertViewIs('admin.tenants.edit');
        $response->assertViewHas('tenant');
    }

    public function test_superadmin_can_update_tenant(): void
    {
        $data = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => '6289876543210',
        ];

        $response = $this->actingAs($this->superadmin)->put(route('admin.tenants.update', $this->tenant), $data);

        $response->assertRedirect(route('admin.tenants.show', $this->tenant));
        $this->assertDatabaseHas('tenants', [
            'id' => $this->tenant->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
    }

    public function test_update_tenant_validates_unique_email(): void
    {
        $otherTenant = Tenant::factory()->create();

        $response = $this->actingAs($this->superadmin)->put(route('admin.tenants.update', $this->tenant), [
            'name' => 'Test',
            'email' => $otherTenant->email,
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_update_tenant_allows_same_email(): void
    {
        $response = $this->actingAs($this->superadmin)->put(route('admin.tenants.update', $this->tenant), [
            'name' => 'Updated Name',
            'email' => $this->tenant->email,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    // --- Suspend Tests ---

    public function test_superadmin_can_suspend_tenant(): void
    {
        $response = $this->actingAs($this->superadmin)->post(route('admin.tenants.suspend', $this->tenant), [
            'reason' => 'Violation of terms of service',
        ]);

        $response->assertRedirect(route('admin.tenants.show', $this->tenant));
        $this->assertDatabaseHas('tenants', [
            'id' => $this->tenant->id,
            'status' => 'suspended',
            'suspended_reason' => 'Violation of terms of service',
        ]);
        $this->assertNotNull($this->tenant->fresh()->suspended_at);
    }

    public function test_suspend_requires_reason(): void
    {
        $response = $this->actingAs($this->superadmin)->post(route('admin.tenants.suspend', $this->tenant), []);

        $response->assertSessionHasErrors(['reason']);
    }

    public function test_cannot_suspend_already_suspended_tenant(): void
    {
        $suspendedTenant = Tenant::factory()->suspended()->create();

        $response = $this->actingAs($this->superadmin)->post(route('admin.tenants.suspend', $suspendedTenant), [
            'reason' => 'Another reason',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // --- Activate Tests ---

    public function test_superadmin_can_activate_suspended_tenant(): void
    {
        $suspendedTenant = Tenant::factory()->suspended()->create();

        $response = $this->actingAs($this->superadmin)->post(route('admin.tenants.activate', $suspendedTenant));

        $response->assertRedirect(route('admin.tenants.show', $suspendedTenant));
        $this->assertDatabaseHas('tenants', [
            'id' => $suspendedTenant->id,
            'status' => 'active',
            'suspended_at' => null,
            'suspended_reason' => null,
        ]);
    }

    public function test_cannot_activate_already_active_tenant(): void
    {
        $response = $this->actingAs($this->superadmin)->post(route('admin.tenants.activate', $this->tenant));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // --- Extend Trial Tests ---

    public function test_superadmin_can_extend_trial(): void
    {
        $trialTenant = Tenant::factory()->trial()->create();
        $originalEnd = $trialTenant->trial_ends_at;

        $response = $this->actingAs($this->superadmin)->post(route('admin.tenants.extend-trial', $trialTenant), [
            'days' => 7,
        ]);

        $response->assertRedirect(route('admin.tenants.show', $trialTenant));
        $this->assertTrue($trialTenant->fresh()->trial_ends_at->greaterThan($originalEnd));
    }

    public function test_cannot_extend_trial_for_non_trial_tenant(): void
    {
        $response = $this->actingAs($this->superadmin)->post(route('admin.tenants.extend-trial', $this->tenant), [
            'days' => 7,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_extend_trial_validates_days(): void
    {
        $trialTenant = Tenant::factory()->trial()->create();

        $response = $this->actingAs($this->superadmin)->post(route('admin.tenants.extend-trial', $trialTenant), [
            'days' => 0,
        ]);

        $response->assertSessionHasErrors(['days']);
    }

    // --- Delete Tests ---

    public function test_superadmin_can_delete_tenant(): void
    {
        $tenantToDelete = Tenant::factory()->create();

        $response = $this->actingAs($this->superadmin)->delete(route('admin.tenants.destroy', $tenantToDelete));

        $response->assertRedirect(route('admin.tenants.index'));
        $this->assertDatabaseMissing('tenants', ['id' => $tenantToDelete->id]);
    }

    public function test_cannot_delete_tenant_with_active_devices(): void
    {
        Device::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'connected',
        ]);

        $response = $this->actingAs($this->superadmin)->delete(route('admin.tenants.destroy', $this->tenant));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('tenants', ['id' => $this->tenant->id]);
    }

    public function test_cannot_delete_tenant_with_pending_jobs(): void
    {
        MessageLog::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->superadmin)->delete(route('admin.tenants.destroy', $this->tenant));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('tenants', ['id' => $this->tenant->id]);
    }
}
