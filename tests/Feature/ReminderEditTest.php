<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Plan;
use App\Models\Reminder;
use App\Models\Subscription;
use App\Models\Template;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReminderEditTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function createTenantWithUser(): array
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->for($tenant)->create(['role' => 'admin']);
        $plan = Plan::factory()->pro()->create();
        Subscription::factory()->for($tenant)->for($plan)->create(['status' => 'active']);

        return [$tenant, $user];
    }

    #[Test]
    public function edit_page_renders_all_form_fields(): void
    {
        [$tenant, $user] = $this->createTenantWithUser();
        $device = Device::factory()->connected()->create(['tenant_id' => $tenant->id]);
        $template = Template::factory()->create(['tenant_id' => $tenant->id]);

        $reminder = Reminder::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'template_id' => $template->id,
            'name' => 'Test Reminder',
            'type' => 'spp_due',
            'frequency' => 'daily',
            'send_time' => '09:30',
            'message' => 'Hello {nama}, bayar SPP {jumlah}',
            'recipients' => ['6281234567890', '6281234567891'],
        ]);

        $response = $this->actingAs($user)->get(route('reminders.edit', $reminder));

        $response->assertOk();
        $response->assertViewIs('reminders.edit');

        // Verify all fields are present in the rendered HTML
        $response->assertSee('name="name"', false);
        $response->assertSee('name="type"', false);
        $response->assertSee('name="frequency"', false);
        $response->assertSee('name="send_time"', false);
        $response->assertSee('name="send_day"', false);
        $response->assertSee('name="device_id"', false);
        $response->assertSee('name="template_id"', false);
        $response->assertSee('name="message"', false);
        $response->assertSee('name="recipients_text"', false);

        // Verify existing values are populated
        $response->assertSee('Test Reminder');
        $response->assertSee('Hello {nama}, bayar SPP {jumlah}');
        $response->assertSee('6281234567890');
        $response->assertSee('6281234567891');
    }

    #[Test]
    public function edit_page_populates_existing_reminder_values(): void
    {
        [$tenant, $user] = $this->createTenantWithUser();
        $device = Device::factory()->connected()->create(['tenant_id' => $tenant->id]);

        $reminder = Reminder::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'name' => 'Invoice Reminder',
            'type' => 'invoice_unpaid',
            'frequency' => 'weekly',
            'send_time' => '14:00',
            'send_day' => 3,
            'message' => 'Bayar invoice Anda',
            'recipients' => ['6289876543210'],
        ]);

        $response = $this->actingAs($user)->get(route('reminders.edit', $reminder));

        $response->assertOk();
        $response->assertSee('Invoice Reminder');
        $response->assertSee('Bayar invoice Anda');
        $response->assertSee('6289876543210');
        $response->assertSee('value="invoice_unpaid"', false);
        $response->assertSee('value="weekly"', false);
    }

    #[Test]
    public function update_reminder_with_all_fields(): void
    {
        [$tenant, $user] = $this->createTenantWithUser();
        $device = Device::factory()->connected()->create(['tenant_id' => $tenant->id]);

        $reminder = Reminder::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'name' => 'Old Name',
            'type' => 'spp_due',
            'frequency' => 'daily',
            'send_time' => '08:00',
            'message' => 'Old message',
            'recipients' => ['6281111111111'],
        ]);

        $response = $this->actingAs($user)->put(route('reminders.update', $reminder), [
            'name' => 'Updated Reminder',
            'type' => 'invoice_unpaid',
            'frequency' => 'weekly',
            'send_time' => '10:00',
            'send_day' => 2,
            'device_id' => $device->id,
            'message' => 'Updated message {nama}',
            'recipients_text' => "6282222222222\n6283333333333",
        ]);

        $response->assertRedirect(route('reminders.show', $reminder));

        $reminder->refresh();
        $this->assertEquals('Updated Reminder', $reminder->name);
        $this->assertEquals('invoice_unpaid', $reminder->type);
        $this->assertEquals('weekly', $reminder->frequency);
        $this->assertEquals('10:00', Carbon::parse($reminder->send_time)->format('H:i'));
        $this->assertEquals(2, $reminder->send_day);
        $this->assertEquals('Updated message {nama}', $reminder->message);
        $this->assertEquals(['6282222222222', '6283333333333'], $reminder->recipients);
    }

    #[Test]
    public function update_requires_message_when_no_template_selected(): void
    {
        [$tenant, $user] = $this->createTenantWithUser();
        $device = Device::factory()->connected()->create(['tenant_id' => $tenant->id]);

        $reminder = Reminder::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'type' => 'spp_due',
            'frequency' => 'daily',
            'send_time' => '08:00',
            'message' => 'Some message',
            'recipients' => ['6281111111111'],
        ]);

        $response = $this->actingAs($user)->put(route('reminders.update', $reminder), [
            'name' => 'Test',
            'type' => 'spp_due',
            'frequency' => 'daily',
            'send_time' => '08:00',
            'device_id' => $device->id,
            'recipients_text' => '6281111111111',
            // No message and no template_id
        ]);

        $response->assertSessionHasErrors('message');
    }
}
