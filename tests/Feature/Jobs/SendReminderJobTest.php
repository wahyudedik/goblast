<?php

namespace Tests\Feature\Jobs;

use App\Jobs\SendMessageJob;
use App\Jobs\SendReminderJob;
use App\Models\Device;
use App\Models\MessageLog;
use App\Models\Plan;
use App\Models\Reminder;
use App\Models\ReminderLog;
use App\Models\Subscription;
use App\Models\Template;
use App\Models\Tenant;
use App\Services\MessageService;
use App\Services\QuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SendReminderJobTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Device $device;

    protected Template $template;

    protected Reminder $reminder;

    protected function setUp(): void
    {
        parent::setUp();

        // Create tenant with active subscription
        $this->tenant = Tenant::factory()->create(['status' => 'active']);

        $plan = Plan::factory()->pro()->create();

        $subscription = Subscription::factory()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'message_quota_limit' => 1000,
            'message_quota_used' => 0,
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->addDays(25),
        ]);

        // Create connected device
        $this->device = Device::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'connected',
        ]);

        // Create template
        $this->template = Template::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'reminder',
            'content' => 'Halo {nama}, reminder untuk {tanggal}',
        ]);

        // Create active reminder
        $this->reminder = Reminder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'device_id' => $this->device->id,
            'template_id' => $this->template->id,
            'type' => 'spp_due',
            'is_active' => true,
        ]);
    }

    public function test_job_skips_if_reminder_is_not_active(): void
    {
        Queue::fake();

        $this->reminder->update(['is_active' => false]);

        $job = new SendReminderJob($this->reminder->fresh());
        $job->handle(
            app(MessageService::class),
            app(QuotaService::class)
        );

        // No messages should be created
        $this->assertDatabaseCount('message_logs', 0);
        Queue::assertNothingPushed();
    }

    public function test_job_skips_if_device_is_not_connected(): void
    {
        Queue::fake();

        $this->device->update(['status' => 'disconnected']);

        $job = new SendReminderJob($this->reminder->fresh());
        $job->handle(
            app(MessageService::class),
            app(QuotaService::class)
        );

        // No messages should be created
        $this->assertDatabaseCount('message_logs', 0);
        Queue::assertNothingPushed();
    }

    public function test_job_updates_last_run_at_after_execution(): void
    {
        Queue::fake();

        $this->assertNull($this->reminder->last_run_at);

        // Use the actual job - it will return empty recipients by default
        // since the placeholder methods return empty collections
        $job = new SendReminderJob($this->reminder);
        $job->handle(
            app(MessageService::class),
            app(QuotaService::class)
        );

        $this->reminder->refresh();
        $this->assertNotNull($this->reminder->last_run_at);
    }

    public function test_job_prevents_duplicate_sends_within_24_hours(): void
    {
        Queue::fake();

        $phone = '6281234567890';
        $conditionKey = 'test_condition_1';

        // Create a reminder log from 12 hours ago
        ReminderLog::create([
            'reminder_id' => $this->reminder->id,
            'recipient' => $phone,
            'condition_key' => $conditionKey,
            'sent_at' => now()->subHours(12),
        ]);

        // Mock the getRecipientsForReminderType to return this recipient
        $job = new class($this->reminder) extends SendReminderJob
        {
            protected function getRecipientsForReminderType(): Collection
            {
                return collect([
                    [
                        'phone' => '6281234567890',
                        'condition_key' => 'test_condition_1',
                        'context' => ['nama' => 'Test', 'tanggal' => '2026-05-01'],
                    ],
                ]);
            }
        };

        $job->handle(
            app(MessageService::class),
            app(QuotaService::class)
        );

        // No new message should be created (duplicate within 24 hours)
        $this->assertDatabaseCount('message_logs', 0);
        Queue::assertNothingPushed();
    }

    public function test_job_allows_send_after_24_hours(): void
    {
        Queue::fake();

        $phone = '6281234567890';
        $conditionKey = 'test_condition_1';

        // Create a reminder log from 25 hours ago (outside 24-hour window)
        ReminderLog::create([
            'reminder_id' => $this->reminder->id,
            'recipient' => $phone,
            'condition_key' => $conditionKey,
            'sent_at' => now()->subHours(25),
        ]);

        // Mock the getRecipientsForReminderType to return this recipient
        $job = new class($this->reminder) extends SendReminderJob
        {
            protected function getRecipientsForReminderType(): Collection
            {
                return collect([
                    [
                        'phone' => '6281234567890',
                        'condition_key' => 'test_condition_1',
                        'context' => ['nama' => 'Test', 'tanggal' => '2026-05-01'],
                    ],
                ]);
            }
        };

        $job->handle(
            app(MessageService::class),
            app(QuotaService::class)
        );

        // Message should be created (outside 24-hour window)
        $this->assertDatabaseCount('message_logs', 1);
        $this->assertDatabaseHas('message_logs', [
            'recipient' => $phone,
            'source' => 'reminder',
            'status' => 'pending',
        ]);

        Queue::assertPushed(SendMessageJob::class);
    }

    public function test_job_creates_message_log_and_dispatches_send_job(): void
    {
        Queue::fake();

        $phone = '6281234567890';
        $conditionKey = 'test_condition_1';

        // Mock the getRecipientsForReminderType to return recipients
        $job = new class($this->reminder) extends SendReminderJob
        {
            protected function getRecipientsForReminderType(): Collection
            {
                return collect([
                    [
                        'phone' => '6281234567890',
                        'condition_key' => 'test_condition_1',
                        'context' => ['nama' => 'John Doe', 'tanggal' => '2026-05-01'],
                    ],
                ]);
            }
        };

        $job->handle(
            app(MessageService::class),
            app(QuotaService::class)
        );

        // Check message log was created
        $this->assertDatabaseCount('message_logs', 1);
        $this->assertDatabaseHas('message_logs', [
            'tenant_id' => $this->tenant->id,
            'device_id' => $this->device->id,
            'reminder_id' => $this->reminder->id,
            'template_id' => $this->template->id,
            'recipient' => $phone,
            'status' => 'pending',
            'source' => 'reminder',
        ]);

        // Check message content was rendered
        $messageLog = MessageLog::first();
        $this->assertStringContainsString('John Doe', $messageLog->message);
        $this->assertStringContainsString('2026-05-01', $messageLog->message);

        // Check SendMessageJob was dispatched
        Queue::assertPushed(SendMessageJob::class, function ($job) use ($messageLog) {
            return $job->messageLog->id === $messageLog->id;
        });
    }

    public function test_job_creates_reminder_log_to_prevent_duplicates(): void
    {
        Queue::fake();

        $phone = '6281234567890';
        $conditionKey = 'test_condition_1';

        // Mock the getRecipientsForReminderType to return recipients
        $job = new class($this->reminder) extends SendReminderJob
        {
            protected function getRecipientsForReminderType(): Collection
            {
                return collect([
                    [
                        'phone' => '6281234567890',
                        'condition_key' => 'test_condition_1',
                        'context' => ['nama' => 'John Doe', 'tanggal' => '2026-05-01'],
                    ],
                ]);
            }
        };

        $job->handle(
            app(MessageService::class),
            app(QuotaService::class)
        );

        // Check reminder log was created
        $this->assertDatabaseCount('reminder_logs', 1);
        $this->assertDatabaseHas('reminder_logs', [
            'reminder_id' => $this->reminder->id,
            'recipient' => $phone,
            'condition_key' => $conditionKey,
        ]);
    }

    public function test_job_decrements_quota_for_each_message(): void
    {
        Queue::fake();

        $initialQuota = $this->tenant->subscriptions()->first()->message_quota_used;

        // Mock the getRecipientsForReminderType to return multiple recipients
        $job = new class($this->reminder) extends SendReminderJob
        {
            protected function getRecipientsForReminderType(): Collection
            {
                return collect([
                    [
                        'phone' => '6281234567890',
                        'condition_key' => 'test_condition_1',
                        'context' => ['nama' => 'John', 'tanggal' => '2026-05-01'],
                    ],
                    [
                        'phone' => '6281234567891',
                        'condition_key' => 'test_condition_2',
                        'context' => ['nama' => 'Jane', 'tanggal' => '2026-05-02'],
                    ],
                ]);
            }
        };

        $job->handle(
            app(MessageService::class),
            app(QuotaService::class)
        );

        // Check quota was decremented by 2
        $this->tenant->refresh();
        $subscription = $this->tenant->subscriptions()->first();
        $this->assertEquals($initialQuota + 2, $subscription->message_quota_used);
    }

    public function test_job_stops_when_quota_is_exhausted(): void
    {
        Queue::fake();

        // Set quota to almost exhausted (only 1 message left)
        $plan = Plan::factory()->create(['message_quota' => 10]);
        $subscription = $this->tenant->subscriptions()->first();
        $subscription->update([
            'plan_id' => $plan->id,
            'message_quota_limit' => 10,
            'message_quota_used' => 9,
        ]);

        // Mock the getRecipientsForReminderType to return 3 recipients
        $job = new class($this->reminder) extends SendReminderJob
        {
            protected function getRecipientsForReminderType(): Collection
            {
                return collect([
                    [
                        'phone' => '6281234567890',
                        'condition_key' => 'test_condition_1',
                        'context' => ['nama' => 'John', 'tanggal' => '2026-05-01'],
                    ],
                    [
                        'phone' => '6281234567891',
                        'condition_key' => 'test_condition_2',
                        'context' => ['nama' => 'Jane', 'tanggal' => '2026-05-02'],
                    ],
                    [
                        'phone' => '6281234567892',
                        'condition_key' => 'test_condition_3',
                        'context' => ['nama' => 'Bob', 'tanggal' => '2026-05-03'],
                    ],
                ]);
            }
        };

        $job->handle(
            app(MessageService::class),
            app(QuotaService::class)
        );

        // Only 1 message should be created (quota exhausted after first)
        $this->assertDatabaseCount('message_logs', 1);

        // Quota should be at limit
        $subscription->refresh();
        $this->assertEquals(10, $subscription->message_quota_used);
    }

    public function test_job_handles_multiple_recipients(): void
    {
        Queue::fake();

        // Mock the getRecipientsForReminderType to return multiple recipients
        $job = new class($this->reminder) extends SendReminderJob
        {
            protected function getRecipientsForReminderType(): Collection
            {
                return collect([
                    [
                        'phone' => '6281234567890',
                        'condition_key' => 'test_condition_1',
                        'context' => ['nama' => 'John', 'tanggal' => '2026-05-01'],
                    ],
                    [
                        'phone' => '6281234567891',
                        'condition_key' => 'test_condition_2',
                        'context' => ['nama' => 'Jane', 'tanggal' => '2026-05-02'],
                    ],
                    [
                        'phone' => '6281234567892',
                        'condition_key' => 'test_condition_3',
                        'context' => ['nama' => 'Bob', 'tanggal' => '2026-05-03'],
                    ],
                ]);
            }
        };

        $job->handle(
            app(MessageService::class),
            app(QuotaService::class)
        );

        // Check all 3 messages were created
        $this->assertDatabaseCount('message_logs', 3);
        $this->assertDatabaseCount('reminder_logs', 3);

        // Check SendMessageJob was dispatched 3 times
        Queue::assertPushed(SendMessageJob::class, 3);
    }

    public function test_job_handles_empty_recipients_for_reminder_types(): void
    {
        Queue::fake();

        // Test each valid reminder type returns empty collection by default
        foreach (['spp_due', 'invoice_unpaid', 'booking_tomorrow'] as $type) {
            $reminder = Reminder::factory()->create([
                'tenant_id' => $this->tenant->id,
                'device_id' => $this->device->id,
                'template_id' => $this->template->id,
                'type' => $type,
                'is_active' => true,
            ]);

            $job = new SendReminderJob($reminder);
            $job->handle(
                app(MessageService::class),
                app(QuotaService::class)
            );

            // No messages should be created (no recipients found)
            $this->assertDatabaseCount('message_logs', 0);
        }

        Queue::assertNothingPushed();
    }
}
