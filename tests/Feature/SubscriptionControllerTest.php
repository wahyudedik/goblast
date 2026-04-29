<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_index_page_displays_current_subscription(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'active']);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $plan = Plan::factory()->create([
            'name' => 'Pro Plan',
            'message_quota' => 1000,
        ]);

        $subscription = Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'message_quota_limit' => 1000,
            'message_quota_used' => 250,
            'starts_at' => now(),
            'ends_at' => now()->addDays(30),
        ]);

        $response = $this->actingAs($user)->get(route('subscription.index'));

        $response->assertOk();
        $response->assertSee('Pro Plan');
        $response->assertSee('750'); // Remaining quota
    }

    public function test_subscription_index_page_displays_trial_status(): void
    {
        $tenant = Tenant::factory()->create([
            'status' => 'trial',
            'trial_ends_at' => now()->addDays(5),
        ]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($user)->get(route('subscription.index'));

        $response->assertOk();
        $response->assertSee('Masa Percobaan'); // Indonesian for "Trial Period"
        $response->assertSee('4'); // Days are rounded down
        $response->assertSee('hari'); // Indonesian for "days"
    }

    public function test_subscription_index_page_displays_available_plans(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        Plan::factory()->create([
            'name' => 'Starter',
            'is_active' => true,
        ]);
        Plan::factory()->create([
            'name' => 'Pro',
            'is_active' => true,
        ]);
        Plan::factory()->create([
            'name' => 'Business',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('subscription.index'));

        $response->assertOk();
        $response->assertSee('Starter');
        $response->assertSee('Pro');
        $response->assertSee('Business');
    }

    public function test_subscription_plans_page_displays_all_active_plans(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        Plan::factory()->create([
            'name' => 'Starter Plan',
            'price' => 50000,
            'message_quota' => 100,
            'is_active' => true,
        ]);
        Plan::factory()->create([
            'name' => 'Pro Plan',
            'price' => 150000,
            'message_quota' => 1000,
            'has_reminder' => true,
            'is_active' => true,
        ]);
        Plan::factory()->create([
            'name' => 'Inactive Plan',
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)->get(route('subscription.plans'));

        $response->assertOk();
        $response->assertSee('Starter Plan');
        $response->assertSee('Pro Plan');
        $response->assertDontSee('Inactive Plan');
        $response->assertSee('50.000');
        $response->assertSee('150.000');
    }

    public function test_subscription_plans_page_displays_plan_features(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        Plan::factory()->create([
            'name' => 'Business Plan',
            'message_quota' => null, // Unlimited
            'has_reminder' => true,
            'has_api' => true,
            'has_multi_device' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('subscription.plans'));

        $response->assertOk();
        $response->assertSee('Business Plan');
        $response->assertSee('Tidak terbatas'); // Indonesian for "Unlimited"
        $response->assertSee('Pengingat terjadwal'); // Indonesian for "Scheduled reminders"
        $response->assertSee('Akses API untuk integrasi'); // Indonesian for "API access"
    }

    public function test_subscription_index_requires_authentication(): void
    {
        $response = $this->get(route('subscription.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_subscription_plans_requires_authentication(): void
    {
        $response = $this->get(route('subscription.plans'));

        $response->assertRedirect(route('login'));
    }
}
