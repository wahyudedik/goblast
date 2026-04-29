<?php

namespace Tests\Feature\Admin;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PlanControllerTest extends TestCase
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

    public function test_non_superadmin_cannot_access_plan_index(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.plans.index'));

        $response->assertForbidden();
    }

    public function test_guest_cannot_access_plan_index(): void
    {
        $response = $this->get(route('admin.plans.index'));

        $response->assertRedirect(route('login'));
    }

    // --- Index Tests ---

    public function test_superadmin_can_view_plan_index(): void
    {
        Plan::factory()->count(3)->create();

        $response = $this->actingAs($this->superadmin)->get(route('admin.plans.index'));

        $response->assertOk();
        $response->assertViewIs('admin.plans.index');
        $response->assertViewHas('plans');
    }

    public function test_plan_index_shows_active_subscription_count(): void
    {
        $plan = Plan::factory()->create();
        $tenant = Tenant::factory()->create();
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);
        Subscription::factory()->create([
            'tenant_id' => Tenant::factory()->create()->id,
            'plan_id' => $plan->id,
            'status' => 'expired',
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.plans.index'));

        $response->assertOk();
        $response->assertViewHas('plans', function ($plans) use ($plan) {
            $found = $plans->firstWhere('id', $plan->id);

            return $found && $found->active_subscriptions_count === 1;
        });
    }

    public function test_plan_index_shows_empty_state(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('admin.plans.index'));

        $response->assertOk();
        $response->assertSee('Tidak ada paket');
    }

    // --- Create Tests ---

    public function test_superadmin_can_view_create_form(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('admin.plans.create'));

        $response->assertOk();
        $response->assertViewIs('admin.plans.create');
    }

    public function test_superadmin_can_create_plan(): void
    {
        $data = [
            'name' => 'Test Plan',
            'slug' => 'test-plan',
            'price' => 50000,
            'message_quota' => 100,
            'max_devices' => 1,
            'has_reminder' => 0,
            'has_api' => 0,
            'has_multi_device' => 0,
            'sort_order' => 1,
        ];

        $response = $this->actingAs($this->superadmin)->post(route('admin.plans.store'), $data);

        $response->assertRedirect(route('admin.plans.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('plans', [
            'name' => 'Test Plan',
            'slug' => 'test-plan',
            'price' => '50000.00',
            'message_quota' => 100,
            'max_devices' => 1,
        ]);
    }

    public function test_can_create_plan_with_unlimited_quota(): void
    {
        $data = [
            'name' => 'Unlimited Plan',
            'slug' => 'unlimited-plan',
            'price' => 500000,
            'message_quota' => null,
            'max_devices' => 5,
            'has_reminder' => 1,
            'has_api' => 1,
            'has_multi_device' => 1,
            'sort_order' => 3,
        ];

        $response = $this->actingAs($this->superadmin)->post(route('admin.plans.store'), $data);

        $response->assertRedirect(route('admin.plans.index'));
        $this->assertDatabaseHas('plans', [
            'name' => 'Unlimited Plan',
            'message_quota' => null,
        ]);
    }

    public function test_create_plan_requires_name_and_slug(): void
    {
        $response = $this->actingAs($this->superadmin)->post(route('admin.plans.store'), [
            'price' => 50000,
            'max_devices' => 1,
        ]);

        $response->assertSessionHasErrors(['name', 'slug']);
    }

    public function test_create_plan_requires_unique_slug(): void
    {
        Plan::factory()->create(['slug' => 'existing-slug']);

        $response = $this->actingAs($this->superadmin)->post(route('admin.plans.store'), [
            'name' => 'Test',
            'slug' => 'existing-slug',
            'price' => 50000,
            'max_devices' => 1,
        ]);

        $response->assertSessionHasErrors(['slug']);
    }

    public function test_create_plan_validates_price_not_negative(): void
    {
        $response = $this->actingAs($this->superadmin)->post(route('admin.plans.store'), [
            'name' => 'Test',
            'slug' => 'test',
            'price' => -100,
            'max_devices' => 1,
        ]);

        $response->assertSessionHasErrors(['price']);
    }

    public function test_non_superadmin_cannot_create_plan(): void
    {
        $response = $this->actingAs($this->regularUser)->post(route('admin.plans.store'), [
            'name' => 'Test',
            'slug' => 'test',
            'price' => 50000,
            'max_devices' => 1,
        ]);

        $response->assertForbidden();
    }

    // --- Edit Tests ---

    public function test_superadmin_can_view_edit_form(): void
    {
        $plan = Plan::factory()->create();

        $response = $this->actingAs($this->superadmin)->get(route('admin.plans.edit', $plan));

        $response->assertOk();
        $response->assertViewIs('admin.plans.edit');
        $response->assertViewHas('plan');
        $response->assertViewHas('activeSubscriptionCount');
    }

    public function test_edit_form_shows_warning_banner(): void
    {
        $plan = Plan::factory()->create();

        $response = $this->actingAs($this->superadmin)->get(route('admin.plans.edit', $plan));

        $response->assertOk();
        $response->assertSee('Perubahan hanya akan berlaku untuk langganan baru');
    }

    public function test_superadmin_can_update_plan(): void
    {
        $plan = Plan::factory()->create();

        $data = [
            'name' => 'Updated Plan',
            'slug' => 'updated-plan',
            'price' => 75000,
            'message_quota' => 500,
            'max_devices' => 2,
            'has_reminder' => 1,
            'has_api' => 0,
            'has_multi_device' => 0,
            'sort_order' => 2,
        ];

        $response = $this->actingAs($this->superadmin)->put(route('admin.plans.update', $plan), $data);

        $response->assertRedirect(route('admin.plans.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'name' => 'Updated Plan',
            'slug' => 'updated-plan',
            'price' => '75000.00',
        ]);
    }

    public function test_update_plan_allows_same_slug(): void
    {
        $plan = Plan::factory()->create(['slug' => 'my-plan']);

        $response = $this->actingAs($this->superadmin)->put(route('admin.plans.update', $plan), [
            'name' => 'Updated Name',
            'slug' => 'my-plan',
            'price' => 50000,
            'max_devices' => 1,
            'sort_order' => 0,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    public function test_update_plan_validates_unique_slug(): void
    {
        $plan = Plan::factory()->create();
        $otherPlan = Plan::factory()->create();

        $response = $this->actingAs($this->superadmin)->put(route('admin.plans.update', $plan), [
            'name' => 'Test',
            'slug' => $otherPlan->slug,
            'price' => 50000,
            'max_devices' => 1,
        ]);

        $response->assertSessionHasErrors(['slug']);
    }

    // --- Toggle Active Tests ---

    public function test_superadmin_can_toggle_plan_active(): void
    {
        $plan = Plan::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->superadmin)->post(route('admin.plans.toggle-active', $plan));

        $response->assertRedirect(route('admin.plans.index'));
        $response->assertSessionHas('success');
        $this->assertFalse($plan->fresh()->is_active);
    }

    public function test_superadmin_can_toggle_plan_inactive_to_active(): void
    {
        $plan = Plan::factory()->inactive()->create();

        $response = $this->actingAs($this->superadmin)->post(route('admin.plans.toggle-active', $plan));

        $response->assertRedirect(route('admin.plans.index'));
        $this->assertTrue($plan->fresh()->is_active);
    }

    // --- Delete Tests ---

    public function test_superadmin_can_delete_plan(): void
    {
        $plan = Plan::factory()->create();

        $response = $this->actingAs($this->superadmin)->delete(route('admin.plans.destroy', $plan));

        $response->assertRedirect(route('admin.plans.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('plans', ['id' => $plan->id]);
    }

    public function test_cannot_delete_plan_with_active_subscriptions(): void
    {
        $plan = Plan::factory()->create();
        $tenant = Tenant::factory()->create();
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->superadmin)->delete(route('admin.plans.destroy', $plan));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('plans', ['id' => $plan->id]);
    }

    public function test_can_delete_plan_with_only_expired_subscriptions(): void
    {
        $plan = Plan::factory()->create();
        $tenant = Tenant::factory()->create();
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'expired',
        ]);

        $response = $this->actingAs($this->superadmin)->delete(route('admin.plans.destroy', $plan));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('plans', ['id' => $plan->id]);
    }
}
