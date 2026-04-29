<?php

namespace Tests\Unit\PropertyBased;

use App\Jobs\SendReminderJob;
use App\Models\Device;
use App\Models\Plan;
use App\Models\Reminder;
use App\Models\ReminderLog;
use App\Models\Subscription;
use App\Models\Template;
use App\Models\Tenant;
use App\Services\ReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-based tests for ReminderService correctness properties.
 *
 * These tests verify the following correctness properties:
 * 1. No duplicate reminders within 24 hours
 * 2. All matching recipients get reminder
 * 3. Reminder status tracking is accurate
 */
class ReminderPropertyTest extends TestCase
{
    use RefreshDatabase;

    private ReminderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ReminderService::class);
        Queue::fake();
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PROPERTY 1: No duplicate reminders within 24 hours
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Property: isDuplicate returns true for same recipient, same condition within 24h.
     */
    #[Test]
    public function no_duplicate_reminder_within_24_hours(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->pro()->create();
        Subscription::factory()->active()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
        ]);
        $device = Device::factory()->connected()->create(['tenant_id' => $tenant->id]);
        $template = Template::factory()->for($tenant)->create();

        $reminder = Reminder::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'template_id' => $template->id,
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

        // Property: checkCondition returns false (no unsent recipients)
        $this->assertFalse($this->service->checkCondition($reminder));
    }

    /**
     * Property: After 24 hours, same recipient can receive reminder again.
     */
    #[Test]
    public function reminder_allowed_after_24_hours(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->pro()->create();
        Subscription::factory()->active()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
        ]);
        $device = Device::factory()->connected()->create(['tenant_id' => $tenant->id]);
        $template = Template::factory()->for($tenant)->create();

        $reminder = Reminder::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'template_id' => $template->id,
            'is_active' => true,
            'type' => 'spp_due',
            'recipients' => ['6281234567890'],
        ]);

        // Create an old reminder log (more than 24 hours ago)
        ReminderLog::create([
            'reminder_id' => $reminder->id,
            'recipient' => '6281234567890',
            'condition_key' => 'spp_'.now()->subDays(2)->format('Y-m-d'),
            'sent_at' => now()->subHours(25),
        ]);

        // Property: checkCondition returns true (recipient can receive again)
        $this->assertTrue($this->service->checkCondition($reminder));
    }

    /**
     * Property: Different recipients are independent (no cross-contamination).
     */
    #[Test]
    public function different_recipients_are_independent(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->pro()->create();
        Subscription::factory()->active()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
        ]);
        $device = Device::factory()->connected()->create(['tenant_id' => $tenant->id]);
        $template = Template::factory()->for($tenant)->create();

        $reminder = Reminder::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'template_id' => $template->id,
            'is_active' => true,
            'type' => 'spp_due',
            'recipients' => ['6281234567890', '6281234567891'],
        ]);

        // Create reminder log for first recipient only
        ReminderLog::create([
            'reminder_id' => $reminder->id,
            'recipient' => '6281234567890',
            'condition_key' => 'spp_'.now()->format('Y-m-d'),
            'sent_at' => now()->subHours(1),
        ]);

        // Property: checkCondition returns true (second recipient hasn't received)
        $this->assertTrue($this->service->checkCondition($reminder));
    }

    /**
     * Property: Different condition keys are independent.
     */
    #[Test]
    public function different_condition_keys_are_independent(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->pro()->create();
        Subscription::factory()->active()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
        ]);
        $device = Device::factory()->connected()->create(['tenant_id' => $tenant->id]);
        $template = Template::factory()->for($tenant)->create();

        $reminder = Reminder::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'template_id' => $template->id,
            'is_active' => true,
            'type' => 'spp_due',
            'recipients' => ['6281234567890'],
        ]);

        // Create reminder log for yesterday's condition
        ReminderLog::create([
            'reminder_id' => $reminder->id,
            'recipient' => '6281234567890',
            'condition_key' => 'spp_'.now()->subDay()->format('Y-m-d'),
            'sent_at' => now()->subHours(1),
        ]);

        // Property: checkCondition returns true (today's condition is different)
        $this->assertTrue($this->service->checkCondition($reminder));
    }

    /**
     * Property: Duplicate check is per-reminder (different reminders are independent).
     */
    #[Test]
    public function different_reminders_are_independent(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->pro()->create();
        Subscription::factory()->active()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
        ]);
        $device = Device::factory()->connected()->create(['tenant_id' => $tenant->id]);
        $template = Template::factory()->for($tenant)->create();

        $reminder1 = Reminder::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'template_id' => $template->id,
            'is_active' => true,
            'type' => 'spp_due',
            'recipients' => ['6281234567890'],
        ]);

        $reminder2 = Reminder::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'template_id' => $template->id,
            'is_active' => true,
            'type' => 'invoice_unpaid',
            'recipients' => ['6281234567890'],
        ]);

        // Create reminder log for reminder1
        ReminderLog::create([
            'reminder_id' => $reminder1->id,
            'recipient' => '6281234567890',
            'condition_key' => 'spp_'.now()->format('Y-m-d'),
            'sent_at' => now()->subHours(1),
        ]);

        // Property: reminder1 blocked, reminder2 allowed
        $this->assertFalse($this->service->checkCondition($reminder1));
        $this->assertTrue($this->service->checkCondition($reminder2));
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PROPERTY 2: All matching recipients get reminder
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Property: getRecipients returns all configured recipients.
     */
    #[Test]
    #[DataProvider('recipientCounts')]
    public function get_recipients_returns_all_configured(int $count): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create(['tenant_id' => $tenant->id]);
        $template = Template::factory()->for($tenant)->create();

        $recipients = [];
        for ($i = 0; $i < $count; $i++) {
            $recipients[] = '628'.str_pad((string) ($i + 1000000000), 10, '0', STR_PAD_LEFT);
        }

        $reminder = Reminder::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'template_id' => $template->id,
            'type' => 'spp_due',
            'recipients' => $recipients,
        ]);

        $result = $this->service->getRecipients($reminder);

        // Property: All recipients are returned
        $this->assertCount($count, $result);

        // Property: Each recipient has required structure
        foreach ($result as $recipient) {
            $this->assertArrayHasKey('phone', $recipient);
            $this->assertArrayHasKey('condition_key', $recipient);
            $this->assertArrayHasKey('context', $recipient);
        }
    }

    /**
     * Property: Each recipient has correct condition key prefix based on type.
     */
    #[Test]
    #[DataProvider('reminderTypes')]
    public function recipients_have_correct_condition_key_prefix(string $type, string $expectedPrefix): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create(['tenant_id' => $tenant->id]);
        $template = Template::factory()->for($tenant)->create();

        $reminder = Reminder::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'template_id' => $template->id,
            'type' => $type,
            'recipients' => ['6281234567890'],
        ]);

        $recipients = $this->service->getRecipients($reminder);

        // Property: Condition key starts with correct prefix
        $this->assertStringStartsWith($expectedPrefix, $recipients[0]['condition_key']);
    }

    /**
     * Property: Empty recipients returns empty collection.
     */
    #[Test]
    public function empty_recipients_returns_empty_collection(): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create(['tenant_id' => $tenant->id]);
        $template = Template::factory()->for($tenant)->create();

        $reminder = Reminder::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'template_id' => $template->id,
            'recipients' => [],
        ]);

        $recipients = $this->service->getRecipients($reminder);

        // Property: Empty recipients returns empty collection
        $this->assertTrue($recipients->isEmpty());
    }

    /**
     * Property: Null recipients returns empty collection.
     */
    #[Test]
    public function null_recipients_returns_empty_collection(): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create(['tenant_id' => $tenant->id]);
        $template = Template::factory()->for($tenant)->create();

        $reminder = Reminder::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'template_id' => $template->id,
            'recipients' => null,
        ]);

        $recipients = $this->service->getRecipients($reminder);

        // Property: Null recipients returns empty collection
        $this->assertTrue($recipients->isEmpty());
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PROPERTY 3: Reminder status tracking is accurate
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Property: processReminders dispatches job for each valid reminder.
     */
    #[Test]
    public function process_reminders_dispatches_for_valid_reminders(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->pro()->create();
        Subscription::factory()->active()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
        ]);
        $device = Device::factory()->connected()->create(['tenant_id' => $tenant->id]);
        $template = Template::factory()->for($tenant)->create();

        // Create 3 valid reminders
        for ($i = 0; $i < 3; $i++) {
            Reminder::factory()->create([
                'tenant_id' => $tenant->id,
                'device_id' => $device->id,
                'template_id' => $template->id,
                'is_active' => true,
                'type' => 'spp_due',
                'recipients' => ['628123456789'.$i],
            ]);
        }

        $result = $this->service->processReminders();

        // Property: All valid reminders are processed
        $this->assertEquals(3, $result['processed']);
        Queue::assertPushed(SendReminderJob::class, 3);
    }

    /**
     * Property: Inactive reminders are skipped.
     */
    #[Test]
    public function inactive_reminders_are_skipped(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->pro()->create();
        Subscription::factory()->active()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
        ]);
        $device = Device::factory()->connected()->create(['tenant_id' => $tenant->id]);
        $template = Template::factory()->for($tenant)->create();

        // Create inactive reminder
        Reminder::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'template_id' => $template->id,
            'is_active' => false,
            'recipients' => ['6281234567890'],
        ]);

        $result = $this->service->processReminders();

        // Property: Inactive reminders are not processed
        $this->assertEquals(0, $result['processed']);
        Queue::assertNothingPushed();
    }

    /**
     * Property: Reminders with disconnected devices are skipped.
     */
    #[Test]
    public function reminders_with_disconnected_device_are_skipped(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->pro()->create();
        Subscription::factory()->active()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
        ]);
        $device = Device::factory()->disconnected()->create(['tenant_id' => $tenant->id]);
        $template = Template::factory()->for($tenant)->create();

        Reminder::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'template_id' => $template->id,
            'is_active' => true,
            'recipients' => ['6281234567890'],
        ]);

        $result = $this->service->processReminders();

        // Property: Reminders with disconnected devices are not processed
        $this->assertEquals(0, $result['processed']);
        Queue::assertNothingPushed();
    }

    /**
     * Property: Reminders with expired subscription are skipped.
     */
    #[Test]
    public function reminders_with_expired_subscription_are_skipped(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->pro()->create();
        Subscription::factory()->expired()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
        ]);
        $device = Device::factory()->connected()->create(['tenant_id' => $tenant->id]);
        $template = Template::factory()->for($tenant)->create();

        Reminder::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'template_id' => $template->id,
            'is_active' => true,
            'recipients' => ['6281234567890'],
        ]);

        $result = $this->service->processReminders();

        // Property: Reminders with expired subscription are not processed
        $this->assertEquals(0, $result['processed']);
        Queue::assertNothingPushed();
    }

    /**
     * Property: processReminders returns accurate counts.
     */
    #[Test]
    public function process_reminders_returns_accurate_counts(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->pro()->create();
        Subscription::factory()->active()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
        ]);
        $connectedDevice = Device::factory()->connected()->create(['tenant_id' => $tenant->id]);
        $disconnectedDevice = Device::factory()->disconnected()->create(['tenant_id' => $tenant->id]);
        $template = Template::factory()->for($tenant)->create();

        // 2 valid reminders
        Reminder::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $connectedDevice->id,
            'template_id' => $template->id,
            'is_active' => true,
            'type' => 'spp_due',
            'recipients' => ['6281234567890'],
        ]);

        Reminder::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $connectedDevice->id,
            'template_id' => $template->id,
            'is_active' => true,
            'type' => 'invoice_unpaid',
            'recipients' => ['6281234567891'],
        ]);

        // 1 reminder with empty recipients (will be skipped)
        Reminder::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $connectedDevice->id,
            'template_id' => $template->id,
            'is_active' => true,
            'type' => 'booking_tomorrow',
            'recipients' => [],
        ]);

        $result = $this->service->processReminders();

        // Property: Counts are accurate
        $this->assertEquals(2, $result['processed']);
        $this->assertEquals(1, $result['skipped']);
    }

    /**
     * Property: sendReminder dispatches SendReminderJob.
     */
    #[Test]
    public function send_reminder_dispatches_job(): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create(['tenant_id' => $tenant->id]);
        $template = Template::factory()->for($tenant)->create();

        $reminder = Reminder::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'template_id' => $template->id,
        ]);

        $this->service->sendReminder($reminder);

        // Property: SendReminderJob is dispatched with correct reminder
        Queue::assertPushed(SendReminderJob::class, function ($job) use ($reminder) {
            return $job->reminder->id === $reminder->id;
        });
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PROPERTY: Valid reminder types
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Property: VALID_TYPES constant contains all expected types.
     */
    #[Test]
    public function valid_types_contains_expected_types(): void
    {
        $expectedTypes = ['spp_due', 'invoice_unpaid', 'booking_tomorrow'];

        // Property: All expected types are present
        foreach ($expectedTypes as $type) {
            $this->assertContains($type, ReminderService::VALID_TYPES);
        }

        // Property: No unexpected types
        $this->assertCount(count($expectedTypes), ReminderService::VALID_TYPES);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // DATA PROVIDERS
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Generate recipient counts.
     */
    public static function recipientCounts(): array
    {
        return [
            'single' => [1],
            'few' => [3],
            'several' => [5],
            'many' => [10],
        ];
    }

    /**
     * Generate reminder types with expected prefixes.
     */
    public static function reminderTypes(): array
    {
        return [
            'spp_due' => ['spp_due', 'spp_'],
            'invoice_unpaid' => ['invoice_unpaid', 'invoice_'],
            'booking_tomorrow' => ['booking_tomorrow', 'booking_'],
        ];
    }
}
