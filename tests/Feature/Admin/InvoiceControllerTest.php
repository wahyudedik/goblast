<?php

namespace Tests\Feature\Admin;

use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class InvoiceControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $superadmin;

    private User $regularUser;

    private Tenant $tenant;

    private Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadmin = User::factory()->superadmin()->create();

        $this->tenant = Tenant::factory()->create(['status' => 'active']);
        $this->regularUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'admin',
        ]);

        $this->plan = Plan::factory()->create(['is_active' => true]);
    }

    // --- Authorization Tests ---

    public function test_non_superadmin_cannot_access_invoice_index(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.invoices.index'));

        $response->assertForbidden();
    }

    public function test_guest_cannot_access_invoice_index(): void
    {
        $response = $this->get(route('admin.invoices.index'));

        $response->assertRedirect(route('login'));
    }

    // --- Index Tests ---

    public function test_superadmin_can_view_invoice_index(): void
    {
        Invoice::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'recorded_by' => $this->superadmin->id,
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.invoices.index'));

        $response->assertOk();
        $response->assertViewIs('admin.invoices.index');
        $response->assertViewHas('invoices');
    }

    public function test_invoice_index_shows_empty_state(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('admin.invoices.index'));

        $response->assertOk();
        $response->assertSee('Tidak ada invoice');
    }

    public function test_invoice_index_can_filter_by_tenant(): void
    {
        $otherTenant = Tenant::factory()->create();

        Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'recorded_by' => $this->superadmin->id,
        ]);
        Invoice::factory()->create([
            'tenant_id' => $otherTenant->id,
            'plan_id' => $this->plan->id,
            'recorded_by' => $this->superadmin->id,
        ]);

        $response = $this->actingAs($this->superadmin)
            ->get(route('admin.invoices.index', ['tenant' => $this->tenant->id]));

        $response->assertOk();
        $response->assertViewHas('invoices', function ($invoices) {
            return $invoices->count() === 1
                && $invoices->first()->tenant_id === $this->tenant->id;
        });
    }

    public function test_invoice_index_can_filter_by_plan(): void
    {
        $otherPlan = Plan::factory()->create();

        Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'recorded_by' => $this->superadmin->id,
        ]);
        Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $otherPlan->id,
            'recorded_by' => $this->superadmin->id,
        ]);

        $response = $this->actingAs($this->superadmin)
            ->get(route('admin.invoices.index', ['plan' => $this->plan->id]));

        $response->assertOk();
        $response->assertViewHas('invoices', function ($invoices) {
            return $invoices->count() === 1
                && $invoices->first()->plan_id === $this->plan->id;
        });
    }

    public function test_invoice_index_can_filter_by_date_range(): void
    {
        Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'recorded_by' => $this->superadmin->id,
            'paid_at' => '2025-01-15',
        ]);
        Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'recorded_by' => $this->superadmin->id,
            'paid_at' => '2025-03-15',
        ]);

        $response = $this->actingAs($this->superadmin)
            ->get(route('admin.invoices.index', [
                'date_from' => '2025-01-01',
                'date_to' => '2025-01-31',
            ]));

        $response->assertOk();
        $response->assertViewHas('invoices', function ($invoices) {
            return $invoices->count() === 1;
        });
    }

    // --- Create Tests ---

    public function test_superadmin_can_view_create_form(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('admin.invoices.create'));

        $response->assertOk();
        $response->assertViewIs('admin.invoices.create');
        $response->assertViewHas('tenants');
        $response->assertViewHas('plans');
    }

    public function test_superadmin_can_create_invoice_and_activate_subscription(): void
    {
        $data = [
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'amount' => 100000,
            'duration_days' => 30,
            'paid_at' => '2025-06-01',
            'notes' => 'Pembayaran via transfer',
        ];

        $response = $this->actingAs($this->superadmin)->post(route('admin.invoices.store'), $data);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('invoices', [
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'amount' => '100000.00',
            'duration_days' => 30,
            'recorded_by' => $this->superadmin->id,
            'notes' => 'Pembayaran via transfer',
        ]);

        // Verify subscription was created
        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'status' => 'active',
        ]);
    }

    public function test_create_invoice_extends_existing_subscription_for_same_plan(): void
    {
        $subscription = Subscription::factory()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addDays(15),
        ]);

        $data = [
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'amount' => 100000,
            'duration_days' => 30,
            'paid_at' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->superadmin)->post(route('admin.invoices.store'), $data);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Subscription should be extended, not a new one created
        $subscription->refresh();
        $this->assertEquals(
            now()->addDays(45)->format('Y-m-d'),
            $subscription->ends_at->format('Y-m-d')
        );

        // Only one subscription should exist
        $this->assertEquals(1, Subscription::where('tenant_id', $this->tenant->id)->count());
    }

    public function test_create_invoice_activates_tenant_if_not_active(): void
    {
        $expiredTenant = Tenant::factory()->create(['status' => 'expired']);

        $data = [
            'tenant_id' => $expiredTenant->id,
            'plan_id' => $this->plan->id,
            'amount' => 100000,
            'duration_days' => 30,
            'paid_at' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->superadmin)->post(route('admin.invoices.store'), $data);

        $response->assertRedirect();
        $this->assertEquals('active', $expiredTenant->fresh()->status);
    }

    public function test_create_invoice_requires_tenant_and_plan(): void
    {
        $response = $this->actingAs($this->superadmin)->post(route('admin.invoices.store'), [
            'amount' => 100000,
            'duration_days' => 30,
            'paid_at' => now()->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors(['tenant_id', 'plan_id']);
    }

    public function test_create_invoice_validates_amount_not_negative(): void
    {
        $response = $this->actingAs($this->superadmin)->post(route('admin.invoices.store'), [
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'amount' => -100,
            'duration_days' => 30,
            'paid_at' => now()->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors(['amount']);
    }

    public function test_create_invoice_validates_duration_range(): void
    {
        $response = $this->actingAs($this->superadmin)->post(route('admin.invoices.store'), [
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'amount' => 100000,
            'duration_days' => 400,
            'paid_at' => now()->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors(['duration_days']);
    }

    public function test_non_superadmin_cannot_create_invoice(): void
    {
        $response = $this->actingAs($this->regularUser)->post(route('admin.invoices.store'), [
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'amount' => 100000,
            'duration_days' => 30,
            'paid_at' => now()->format('Y-m-d'),
        ]);

        $response->assertForbidden();
    }

    // --- Show Tests ---

    public function test_superadmin_can_view_invoice_details(): void
    {
        $invoice = Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'recorded_by' => $this->superadmin->id,
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.invoices.show', $invoice));

        $response->assertOk();
        $response->assertViewIs('admin.invoices.show');
        $response->assertViewHas('invoice');
        $response->assertSee($this->tenant->name);
        $response->assertSee($this->plan->name);
    }

    public function test_invoice_show_displays_linked_subscription(): void
    {
        $subscription = Subscription::factory()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'status' => 'active',
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'subscription_id' => $subscription->id,
            'recorded_by' => $this->superadmin->id,
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.invoices.show', $invoice));

        $response->assertOk();
        $response->assertSee('Langganan Terkait');
        $response->assertSee('Active');
    }

    // --- Edit Tests ---

    public function test_superadmin_can_view_edit_form(): void
    {
        $invoice = Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'recorded_by' => $this->superadmin->id,
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.invoices.edit', $invoice));

        $response->assertOk();
        $response->assertViewIs('admin.invoices.edit');
        $response->assertViewHas('invoice');
    }

    public function test_superadmin_can_update_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'recorded_by' => $this->superadmin->id,
            'amount' => 100000,
        ]);

        $data = [
            'amount' => 150000,
            'paid_at' => '2025-06-15',
            'notes' => 'Updated notes',
        ];

        $response = $this->actingAs($this->superadmin)->put(route('admin.invoices.update', $invoice), $data);

        $response->assertRedirect(route('admin.invoices.show', $invoice));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'amount' => '150000.00',
            'notes' => 'Updated notes',
        ]);
    }

    public function test_update_invoice_validates_amount(): void
    {
        $invoice = Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'recorded_by' => $this->superadmin->id,
        ]);

        $response = $this->actingAs($this->superadmin)->put(route('admin.invoices.update', $invoice), [
            'amount' => -500,
            'paid_at' => '2025-06-15',
        ]);

        $response->assertSessionHasErrors(['amount']);
    }

    // --- Delete Tests ---

    public function test_superadmin_can_delete_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'recorded_by' => $this->superadmin->id,
        ]);

        $response = $this->actingAs($this->superadmin)->delete(route('admin.invoices.destroy', $invoice));

        $response->assertRedirect(route('admin.invoices.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
    }

    public function test_non_superadmin_cannot_delete_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'recorded_by' => $this->superadmin->id,
        ]);

        $response = $this->actingAs($this->regularUser)->delete(route('admin.invoices.destroy', $invoice));

        $response->assertForbidden();
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
    }
}
