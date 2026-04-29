<?php

namespace Tests\Unit;

use App\Jobs\SendReminderJob;
use App\Models\Device;
use App\Models\Plan;
use App\Models\Reminder;
use App\Models\ReminderLog;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\ReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ReminderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ReminderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ReminderService::class);
    }

    // ── processReminders() ──────────────────────────────────────────

    public function test_process_reminders_dispatches_jobs_for_active_reminders(): void
    {
        Queue::fake();

        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->pro()->create();
        Subscription::factory()->active()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
        ]);
        $device = Device::factory()->connected()->create(['tenant_id' => $tenant->id]);

        $reminder = Reminder::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'is_active' => true,
            'type' => 'spp_due',
            'recipients' => ['6281234567890'],
        ]);

        $result = $this->service->processReminders();

        $this->assertEquals(1, $result['processed']);
        $this->assertEquals(0, $result['skipped']);
        Queue::assertPushed(SendReminderJob::class);
    }

    public function test_process_reminders_skips_inactive_reminders(): void
    {
        Queue::fake();

        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->pro()->create();
        Subscription::factory()->active()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
        ]);
        $device = Device::factory()->connected()->create(['tenant_id' => $tenant->id]);

        Reminder::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'is_active' => false,
            'recipients' => ['6281234567890'],
        ]);

        $result = $this->service->processReminders();

        $this->assertEquals(0, $result['processed']);
        Queue::assertNothingPushed();
    }

    public function test_process_reminders_skips_reminders_with_disconnected_device(): void
    {
        Queue::fake();

        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->pro()->create();
        Subscription::factory()->active()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
        ]);
        $device = Device::factory()->disconnected()->create(['tenant_id' => $tenant->id]);

        Reminder::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'is_active' => true,
            'recipients' => ['6281234567890'],
        ]);

        $result = $this->service->processReminders();

        $this->assertEquals(0, $result['processed']);
        Queue::assertNothingPushed();
    }

    public function test_process_reminders_returns_correct_counts(): void
    {
        Queue::fake();

        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->pro()->create();
        Subscription::factory()->active()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
        ]);
        $device = Device::factory()->connected()->create(['tenant_id' => $tenant->id]);

        // Active reminder with recipients
        Reminder::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'is_active' => true,
            'type' => 'spp_due',
            'recipients' => ['6281234567890'],
        ]);

        // Active reminder without recipients (will be skipped)
        Reminder::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'is_active' => true,
            'type' => 'invoice_unpaid',
            'recipients' => [],
        ]);

        $result = $this->service->processReminders();

        $this->assertEquals(1, $result['processed']);
        $this->assertEquals(1, $result['skipped']);
    }

    // ── checkCondition() ────────────────────────────────────────────

    public function test_check_condition_returns_true_for_valid_reminder(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->pro()->create();
        Subscription::factory()->active()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
        ]);
        $device = Device::factory()->connected()->create(['tenant_id' => $tenant->id]);

        $reminder = Reminder::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'is_active' => true,
            'recipients' => ['6281234567890'],
        ]);

        $this->assertTrue($this->service->checkCondition($reminder));
    }

    public function test_check_condition_returns_false_for_inactive_reminder(): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->connected()->create(['tenant_id' => $tenant->id]);

        $reminder = Reminder::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'is_active' => false,
            'recipients' => ['6281234567890'],
        ]);

        $this->assertFalse($this->service->checkCondition($reminder));
    }

    public function test_check_condition_returns_false_for_disconnected_device(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->pro()->create();
        Subscription::factory()->active()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
        ]);
        $device = Device::factory()->disconnected()->create(['tenant_id' => $tenant->id]);

        $reminder = Reminder::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'is_active' => true,
            'recipients' => ['6281234567890'],
        ]);

        $this->assertFalse($this->service->checkCondition($reminder));
    }

    public function test_check_condition_returns_false_for_expired_subscription(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->pro()->create();
        Subscription::factory()->expired()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
        ]);
        $device = Device::factory()->connected()->create(['tenant_id' => $tenant->id]);

        $reminder = Reminder::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'is_active' => true,
            'recipients' => ['6281234567890'],
        ]);

        $this->assertFalse($this->service->checkCondition($reminder));
    }

    public function test_check_condition_returns_false_for_empty_recipients(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->pro()->create();
        Subscription::factory()->active()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
        ]);
        $device = Device::factory()->connected()->create(['tenant_id' => $tenant->id]);

        $reminder = Reminder::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'is_active' => true,
            'recipients' => [],
        ]);

        $this->assertFalse($this->service->checkCondition($reminder));
    }

    public function test_check_condition_returns_false_when_all_recipients_already_sent(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->pro()->create();
        Subscription::factory()->active()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
        ]);
        $device = Device::factory()->connected()->create(['tenant_id' => $tenant->id]);

        $reminder = Reminder::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'is_active' => true,
            'type' => 'spp_due',
            'recipients' => ['6281234567890'],
        ]);

        // Create a recent reminder log (within 24 hours)
        ReminderLog::create([
            'reminder_id' => $reminder->id,
            'recipient' => '6281234567890',
            'condition_key' => 'spp_'.now()->format('Y-m-d'),
            'sent_at' => now()->subHours(1),
        ]);

        $this->assertFalse($this->service->checkCondition($reminder));
    }

    // ── sendReminder() ──────────────────────────────────────────────

    public function test_send_reminder_dispatches_job(): void
    {
        Queue::fake();

        $tenant = Tenant::factory()->create();
        $device = Device::factory()->connected()->create(['tenant_id' => $tenant->id]);

        $reminder = Reminder::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
        ]);

        $this->service->sendReminder($reminder);

        Queue::assertPushed(SendReminderJob::class, function ($job) use ($reminder) {
            return $job->reminder->id === $reminder->id;
        });
    }

    // ── getRecipients() ─────────────────────────────────────────────

    public function test_get_recipients_returns_collection_with_correct_structure(): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create(['tenant_id' => $tenant->id]);

        $reminder = Reminder::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'type' => 'spp_due',
            'recipients' => ['6281234567890', '6281234567891'],
        ]);

        $recipients = $this->service->getRecipients($reminder);

        $this->assertCount(2, $recipients);
        $this->assertEquals('6281234567890', $recipients[0]['phone']);
        $this->assertStringStartsWith('spp_', $recipients[0]['condition_key']);
        $this->assertArrayHasKey('nama', $recipients[0]['context']);
        $this->assertArrayHasKey('tanggal', $recipients[0]['context']);
    }

    public function test_get_recipients_returns_empty_collection_for_null_recipients(): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create(['tenant_id' => $tenant->id]);

        $reminder = Reminder::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'recipients' => null,
        ]);

        $recipients = $this->service->getRecipients($reminder);

        $this->assertTrue($recipients->isEmpty());
    }

    public function test_get_recipients_uses_correct_condition_prefix_for_each_type(): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create(['tenant_id' => $tenant->id]);

        $types = [
            'spp_due' => 'spp_',
            'invoice_unpaid' => 'invoice_',
            'booking_tomorrow' => 'booking_',
        ];

        foreach ($types as $type => $expectedPrefix) {
            $reminder = Reminder::factory()->create([
                'tenant_id' => $tenant->id,
                'device_id' => $device->id,
                'type' => $type,
                'recipients' => ['6281234567890'],
            ]);

            $recipients = $this->service->getRecipients($reminder);

            $this->assertStringStartsWith($expectedPrefix, $recipients[0]['condition_key']);
        }
    }

    public function test_valid_types_constant_contains_expected_types(): void
    {
        $this->assertContains('spp_due', ReminderService::VALID_TYPES);
        $this->assertContains('invoice_unpaid', ReminderService::VALID_TYPES);
        $this->assertContains('booking_tomorrow', ReminderService::VALID_TYPES);
        $this->assertCount(3, ReminderService::VALID_TYPES);
    }
}
