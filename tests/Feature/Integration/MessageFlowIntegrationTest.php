<?php

namespace Tests\Feature\Integration;

use App\Jobs\ProcessWebhookJob;
use App\Jobs\SendMessageJob;
use App\Jobs\SendReminderJob;
use App\Models\AutoReplyCooldown;
use App\Models\AutoReplyLog;
use App\Models\Broadcast;
use App\Models\Device;
use App\Models\Invoice;
use App\Models\KeywordRule;
use App\Models\MessageLog;
use App\Models\Plan;
use App\Models\Reminder;
use App\Models\ReminderLog;
use App\Models\Subscription;
use App\Models\Template;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AutoReplyService;
use App\Services\BillingService;
use App\Services\BroadcastService;
use App\Services\Contracts\MessageServiceInterface;
use App\Services\Contracts\QuotaServiceInterface;
use App\Services\QuotaService;
use App\Services\ReminderService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MessageFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    // ══════════════════════════════════════════════════════════════════
    // BROADCAST FLOW INTEGRATION TESTS
    // ══════════════════════════════════════════════════════════════════

    public function test_complete_broadcast_flow_from_csv_to_message_delivery(): void
    {
        // Arrange
        Queue::fake([SendMessageJob::class]);
        Storage::fake('local');

        $tenant = Tenant::factory()->create(['status' => 'active']);
        $plan = Plan::factory()->starter()->create(['message_quota' => 100]);
        $subscription = Subscription::factory()->active()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'message_quota_used' => 0,
            'message_quota_limit' => 100,
        ]);
        $device = Device::factory()->connected()->create(['tenant_id' => $tenant->id]);
        $template = Template::factory()->notification()->create(['tenant_id' => $tenant->id]);

        // Create CSV file with recipients
        $csvContent = "phone\n6281234567890\n6281234567891\n6281234567892";
        $file = UploadedFile::fake()->createWithContent('recipients.csv', $csvContent);

        // Get real services
        $broadcastService = app(BroadcastService::class);

        // Act - Step 1: Create broadcast from CSV
        $broadcast = $broadcastService->createFromCsv($tenant, $file, $device, $template);

        // Assert - Broadcast created correctly
        $this->assertInstanceOf(Broadcast::class, $broadcast);
        $this->assertEquals('draft', $broadcast->status);
        $this->assertEquals(3, $broadcast->total_recipients);
        $this->assertEquals('csv', $broadcast->source_type);
        $this->assertNotNull($broadcast->csv_path);

        // Act - Step 2: Dispatch broadcast
        $broadcastService->dispatch($broadcast);

        // Assert - Broadcast status updated
        $broadcast->refresh();
        $this->assertEquals('running', $broadcast->status);
        $this->assertNotNull($broadcast->started_at);

        // Assert - MessageLogs created for each recipient
        $messageLogs = MessageLog::where('broadcast_id', $broadcast->id)->get();
        $this->assertCount(3, $messageLogs);

        foreach ($messageLogs as $log) {
            $this->assertEquals('pending', $log->status);
            $this->assertEquals('broadcast', $log->source);
            $this->assertEquals($device->id, $log->device_id);
            $this->assertEquals($tenant->id, $log->tenant_id);
            $this->assertNotNull($log->job_id);
        }

        // Assert - SendMessageJob dispatched for each recipient
        Queue::assertPushed(SendMessageJob::class, 3);

        // Assert - Quota decremented
        $subscription->refresh();
        $this->assertEquals(3, $subscription->message_quota_used);
    }

    public function test_broadcast_flow_with_quota_verification(): void
    {
        // Arrange
        Queue::fake([SendMessageJob::class]);
        Storage::fake('local');

        $tenant = Tenant::factory()->create(['status' => 'active']);
        $plan = Plan::factory()->starter()->create(['message_quota' => 100]);
        Subscription::factory()->active()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'message_quota_used' => 98, // Only 2 remaining
            'message_quota_limit' => 100,
        ]);
        $device = Device::factory()->connected()->create(['tenant_id' => $tenant->id]);
        $template = Template::factory()->create(['tenant_id' => $tenant->id]);

        $recipients = ['6281234567890', '6281234567891', '6281234567892'];

        $broadcastService = app(BroadcastService::class);

        // Act - Create and dispatch broadcast
        $broadcast = $broadcastService->createFromRecipients($tenant, $recipients, $device, $template);
        $broadcastService->dispatch($broadcast);

        // Assert - Only 2 messages dispatched due to quota limit
        $messageLogs = MessageLog::where('broadcast_id', $broadcast->id)->get();
        $this->assertCount(2, $messageLogs);

        Queue::assertPushed(SendMessageJob::class, 2);
    }

    public function test_broadcast_progress_tracking(): void
    {
        // Arrange
        Storage::fake('local');

        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->business()->create();
        Subscription::factory()->active()->unlimited()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
        ]);
        $device = Device::factory()->connected()->create(['tenant_id' => $tenant->id]);
        $template = Template::factory()->create(['tenant_id' => $tenant->id]);

        $broadcast = Broadcast::factory()->running()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'template_id' => $template->id,
            'total_recipients' => 10,
        ]);

        // Create message logs with different statuses
        MessageLog::factory()->for($tenant)->for($device)->for($broadcast)->count(5)->create(['status' => 'sent']);
        MessageLog::factory()->for($tenant)->for($device)->for($broadcast)->count(2)->create(['status' => 'failed']);
        MessageLog::factory()->for($tenant)->for($device)->for($broadcast)->count(3)->create(['status' => 'pending']);

        $broadcastService = app(BroadcastService::class);

        // Act
        $progress = $broadcastService->getProgress($broadcast);

        // Assert
        $this->assertEquals(10, $progress->total);
        $this->assertEquals(5, $progress->sent);
        $this->assertEquals(2, $progress->failed);
        $this->assertEquals(3, $progress->pending);
        $this->assertEquals(70.0, $progress->percentage);
    }

    // ══════════════════════════════════════════════════════════════════
    // AUTO-REPLY FLOW INTEGRATION TESTS
    // ══════════════════════════════════════════════════════════════════

    public function test_complete_auto_reply_flow_from_webhook_to_reply(): void
    {
        // Arrange
        Queue::fake([SendMessageJob::class, ProcessWebhookJob::class]);

        $tenant = Tenant::factory()->create(['status' => 'active']);
        $plan = Plan::factory()->pro()->create();
        Subscription::factory()->active()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
        ]);
        $device = Device::factory()->connected()->create([
            'tenant_id' => $tenant->id,
            'gateway_device_id' => 'test-device-123',
        ]);
        $keywordRule = KeywordRule::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'keyword' => 'harga',
            'reply' => 'Harga produk kami mulai dari Rp 100.000',
            'is_active' => true,
            'priority' => 1,
        ]);

        $autoReplyService = new AutoReplyService;

        // Act - Process incoming message
        $autoReplyService->processIncomingMessage(
            'test-device-123',
            '6281234567890',
            'Berapa harga produknya?'
        );

        // Assert - AutoReplyLog created
        $this->assertDatabaseHas('auto_reply_logs', [
            'device_id' => $device->id,
            'keyword_rule_id' => $keywordRule->id,
            'from' => '6281234567890',
            'message' => 'Berapa harga produknya?',
            'matched' => true,
            'reply_sent' => true,
        ]);

        // Assert - MessageLog created for reply
        $messageLog = MessageLog::where('source', 'auto_reply')
            ->where('recipient', '6281234567890')
            ->first();

        $this->assertNotNull($messageLog);
        $this->assertEquals('Harga produk kami mulai dari Rp 100.000', $messageLog->message);
        $this->assertEquals('pending', $messageLog->status);

        // Assert - SendMessageJob dispatched
        Queue::assertPushed(SendMessageJob::class, function ($job) use ($messageLog) {
            return $job->messageLog->id === $messageLog->id;
        });

        // Assert - Cooldown set
        $this->assertDatabaseHas('auto_reply_cooldowns', [
            'device_id' => $device->id,
            'keyword_rule_id' => $keywordRule->id,
            'from' => '6281234567890',
        ]);
    }

    public function test_auto_reply_respects_cooldown_period(): void
    {
        // Arrange
        Queue::fake([SendMessageJob::class]);

        $tenant = Tenant::factory()->create(['status' => 'active']);
        $device = Device::factory()->connected()->create([
            'tenant_id' => $tenant->id,
            'gateway_device_id' => 'test-device-456',
        ]);
        $keywordRule = KeywordRule::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'keyword' => 'promo',
            'reply' => 'Promo diskon 50%!',
            'is_active' => true,
        ]);

        // Create active cooldown (not expired)
        AutoReplyCooldown::create([
            'device_id' => $device->id,
            'keyword_rule_id' => $keywordRule->id,
            'from' => '6281234567890',
            'expires_at' => now()->addMinutes(30),
        ]);

        $autoReplyService = new AutoReplyService;

        // Act - Process incoming message (should be blocked by cooldown)
        $autoReplyService->processIncomingMessage(
            'test-device-456',
            '6281234567890',
            'Ada promo apa?'
        );

        // Assert - AutoReplyLog created but reply not sent
        $this->assertDatabaseHas('auto_reply_logs', [
            'device_id' => $device->id,
            'from' => '6281234567890',
            'matched' => true,
            'reply_sent' => false,
        ]);

        // Assert - No SendMessageJob dispatched
        Queue::assertNothingPushed();
    }

    public function test_auto_reply_selects_highest_priority_keyword(): void
    {
        // Arrange
        Queue::fake([SendMessageJob::class]);

        $tenant = Tenant::factory()->create(['status' => 'active']);
        $device = Device::factory()->connected()->create([
            'tenant_id' => $tenant->id,
            'gateway_device_id' => 'test-device-789',
        ]);

        // Low priority rule
        KeywordRule::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'keyword' => 'info',
            'reply' => 'Info umum',
            'is_active' => true,
            'priority' => 1,
        ]);

        // High priority rule
        $highPriorityRule = KeywordRule::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'keyword' => 'harga',
            'reply' => 'Info harga spesial',
            'is_active' => true,
            'priority' => 10,
        ]);

        $autoReplyService = new AutoReplyService;

        // Act - Message contains both keywords
        $autoReplyService->processIncomingMessage(
            'test-device-789',
            '6281234567890',
            'Mau info harga dong'
        );

        // Assert - High priority rule matched
        $this->assertDatabaseHas('auto_reply_logs', [
            'device_id' => $device->id,
            'keyword_rule_id' => $highPriorityRule->id,
            'matched' => true,
        ]);

        // Assert - Reply uses high priority message
        $messageLog = MessageLog::where('source', 'auto_reply')->first();
        $this->assertEquals('Info harga spesial', $messageLog->message);
    }

    public function test_webhook_integration_dispatches_auto_reply_processing(): void
    {
        // Arrange
        Queue::fake();

        $tenant = Tenant::factory()->create();
        $device = Device::factory()->connected()->create([
            'tenant_id' => $tenant->id,
            'gateway_device_id' => 'webhook-device-123',
        ]);

        $payload = [
            'event' => 'message.received',
            'device_id' => 'webhook-device-123',
            'from' => '6281234567890',
            'message' => 'test message',
            'timestamp' => now()->timestamp * 1000,
        ];

        $secret = config('wa-automation.baileys.webhook_secret');
        $signature = hash_hmac('sha256', json_encode($payload), $secret);

        // Act
        $response = $this->postJson('/webhook/baileys', $payload, [
            'X-Baileys-Signature' => $signature,
        ]);

        // Assert
        $response->assertStatus(200);
        Queue::assertPushed(ProcessWebhookJob::class, function ($job) use ($payload) {
            return $job->payload === $payload;
        });
    }

    // ══════════════════════════════════════════════════════════════════
    // REMINDER FLOW INTEGRATION TESTS
    // ══════════════════════════════════════════════════════════════════

    public function test_complete_reminder_flow_from_config_to_message(): void
    {
        // Arrange
        Queue::fake([SendMessageJob::class, SendReminderJob::class]);

        $tenant = Tenant::factory()->create(['status' => 'active']);
        $plan = Plan::factory()->pro()->create();
        Subscription::factory()->active()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'message_quota_used' => 0,
            'message_quota_limit' => 1000,
        ]);
        $device = Device::factory()->connected()->create(['tenant_id' => $tenant->id]);
        $template = Template::factory()->reminder()->create(['tenant_id' => $tenant->id]);

        $reminder = Reminder::factory()->sppDue()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'template_id' => $template->id,
            'is_active' => true,
            'recipients' => ['6281234567890', '6281234567891'],
        ]);

        $reminderService = app(ReminderService::class);

        // Act - Process reminders
        $result = $reminderService->processReminders();

        // Assert - Reminder processed
        $this->assertEquals(1, $result['processed']);
        $this->assertEquals(0, $result['skipped']);

        // Assert - SendReminderJob dispatched
        Queue::assertPushed(SendReminderJob::class, function ($job) use ($reminder) {
            return $job->reminder->id === $reminder->id;
        });
    }

    public function test_reminder_job_creates_message_logs_and_reminder_logs(): void
    {
        // Arrange
        Queue::fake([SendMessageJob::class]);

        $tenant = Tenant::factory()->create(['status' => 'active']);
        $plan = Plan::factory()->pro()->create();
        Subscription::factory()->active()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'message_quota_used' => 0,
            'message_quota_limit' => 1000,
        ]);
        $device = Device::factory()->connected()->create(['tenant_id' => $tenant->id]);
        $template = Template::factory()->create([
            'tenant_id' => $tenant->id,
            'content' => 'Pengingat untuk {nama}',
        ]);

        $reminder = Reminder::factory()->invoiceUnpaid()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'template_id' => $template->id,
            'is_active' => true,
            'recipients' => ['6281234567890', '6281234567891'],
        ]);

        // Act - Execute SendReminderJob directly
        $job = new SendReminderJob($reminder);
        $job->handle(
            app(MessageServiceInterface::class),
            app(QuotaServiceInterface::class)
        );

        // Assert - MessageLogs created
        $messageLogs = MessageLog::where('reminder_id', $reminder->id)->get();
        $this->assertCount(2, $messageLogs);

        foreach ($messageLogs as $log) {
            $this->assertEquals('pending', $log->status);
            $this->assertEquals('reminder', $log->source);
        }

        // Assert - ReminderLogs created
        $reminderLogs = ReminderLog::where('reminder_id', $reminder->id)->get();
        $this->assertCount(2, $reminderLogs);

        // Assert - SendMessageJob dispatched for each recipient
        Queue::assertPushed(SendMessageJob::class, 2);
    }

    public function test_reminder_prevents_duplicate_within_24_hours(): void
    {
        // Arrange
        Queue::fake([SendMessageJob::class]);

        $tenant = Tenant::factory()->create(['status' => 'active']);
        $plan = Plan::factory()->pro()->create();
        Subscription::factory()->active()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
        ]);
        $device = Device::factory()->connected()->create(['tenant_id' => $tenant->id]);
        $template = Template::factory()->create(['tenant_id' => $tenant->id]);

        $reminder = Reminder::factory()->sppDue()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'template_id' => $template->id,
            'is_active' => true,
            'recipients' => ['6281234567890'],
        ]);

        // Create existing ReminderLog within 24 hours
        ReminderLog::create([
            'reminder_id' => $reminder->id,
            'recipient' => '6281234567890',
            'condition_key' => 'spp_'.now()->format('Y-m-d'),
            'sent_at' => now()->subHours(1),
        ]);

        // Act - Execute SendReminderJob
        $job = new SendReminderJob($reminder);
        $job->handle(
            app(MessageServiceInterface::class),
            app(QuotaServiceInterface::class)
        );

        // Assert - No new MessageLog created (duplicate prevented)
        $messageLogs = MessageLog::where('reminder_id', $reminder->id)->get();
        $this->assertCount(0, $messageLogs);

        // Assert - No SendMessageJob dispatched
        Queue::assertNothingPushed();
    }

    public function test_reminder_process_command_dispatches_jobs(): void
    {
        // Arrange
        Queue::fake([SendReminderJob::class]);

        $tenant = Tenant::factory()->create(['status' => 'active']);
        $plan = Plan::factory()->pro()->create();
        Subscription::factory()->active()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
        ]);
        $device = Device::factory()->connected()->create(['tenant_id' => $tenant->id]);
        $template = Template::factory()->create(['tenant_id' => $tenant->id]);

        // Create reminder with current time
        $currentTime = now()->format('H:i');
        Reminder::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'template_id' => $template->id,
            'is_active' => true,
            'frequency' => 'daily',
            'send_time' => $currentTime,
            'recipients' => ['6281234567890'],
        ]);

        // Act - Run the command
        $this->artisan('reminder:process')
            ->assertSuccessful();

        // Assert - SendReminderJob dispatched
        Queue::assertPushed(SendReminderJob::class);
    }

    // ══════════════════════════════════════════════════════════════════
    // SUBSCRIPTION LIFECYCLE INTEGRATION TESTS
    // ══════════════════════════════════════════════════════════════════

    public function test_complete_subscription_lifecycle_from_trial_to_active(): void
    {
        // Arrange
        Notification::fake();

        $tenant = Tenant::factory()->trial()->create();
        $plan = Plan::factory()->starter()->create(['message_quota' => 100]);
        $superadmin = User::factory()->superadmin()->create();

        $billingService = app(BillingService::class);
        $subscriptionService = app(SubscriptionService::class);

        // Assert - Initial state is trial
        $this->assertEquals('trial', $tenant->status);

        // Act - Record payment and activate subscription
        $paymentData = [
            'amount' => 50000,
            'duration_days' => 30,
            'paid_at' => now()->format('Y-m-d'),
            'notes' => 'Transfer BCA',
        ];

        $subscription = $billingService->activateSubscription($tenant, $plan, $paymentData, $superadmin);

        // Assert - Subscription created correctly
        $this->assertInstanceOf(Subscription::class, $subscription);
        $this->assertEquals('active', $subscription->status);
        $this->assertEquals(0, $subscription->message_quota_used);
        $this->assertEquals(100, $subscription->message_quota_limit);
        $this->assertTrue($subscription->starts_at->isToday());
        $this->assertTrue($subscription->ends_at->isSameDay(now()->addDays(30)));

        // Assert - Tenant status updated
        $tenant->refresh();
        $this->assertEquals('active', $tenant->status);

        // Assert - Invoice created and linked
        $invoice = Invoice::where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals($subscription->id, $invoice->subscription_id);
        $this->assertEquals('50000.00', $invoice->amount);
    }

    public function test_subscription_quota_reset_on_new_subscription(): void
    {
        // Arrange
        Notification::fake();

        $tenant = Tenant::factory()->create(['status' => 'active']);
        $plan = Plan::factory()->starter()->create(['message_quota' => 100]);
        $superadmin = User::factory()->superadmin()->create();

        // Create existing subscription with used quota
        $oldSubscription = Subscription::factory()->active()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'message_quota_used' => 80,
            'message_quota_limit' => 100,
        ]);

        $billingService = app(BillingService::class);

        // Act - Activate new subscription
        $paymentData = [
            'amount' => 50000,
            'duration_days' => 30,
            'paid_at' => now()->format('Y-m-d'),
        ];

        $newSubscription = $billingService->activateSubscription($tenant, $plan, $paymentData, $superadmin);

        // Assert - Old subscription expired
        $oldSubscription->refresh();
        $this->assertEquals('expired', $oldSubscription->status);

        // Assert - New subscription has reset quota
        $this->assertEquals(0, $newSubscription->message_quota_used);
        $this->assertEquals(100, $newSubscription->message_quota_limit);
    }

    public function test_subscription_extension_maintains_quota(): void
    {
        // Arrange
        Notification::fake();

        $tenant = Tenant::factory()->create(['status' => 'active']);
        $plan = Plan::factory()->starter()->create(['message_quota' => 100]);
        $superadmin = User::factory()->superadmin()->create();

        $originalEndsAt = now()->addDays(10);
        $subscription = Subscription::factory()->active()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'message_quota_used' => 50,
            'message_quota_limit' => 100,
            'ends_at' => $originalEndsAt,
        ]);

        $billingService = app(BillingService::class);

        // Act - Extend subscription
        $paymentData = [
            'amount' => 50000,
            'duration_days' => 30,
            'paid_at' => now()->format('Y-m-d'),
        ];

        $invoice = $billingService->extendSubscription($tenant, $plan, $paymentData, $superadmin);

        // Assert - Invoice created
        $this->assertNotNull($invoice);
        $this->assertEquals($subscription->id, $invoice->subscription_id);

        // Assert - Subscription extended
        $subscription->refresh();
        $this->assertTrue($subscription->ends_at->isSameDay($originalEndsAt->copy()->addDays(30)));

        // Assert - Quota maintained (not reset)
        $this->assertEquals(50, $subscription->message_quota_used);
    }

    public function test_subscription_expiry_updates_tenant_status(): void
    {
        // Arrange
        Notification::fake();

        $tenant = Tenant::factory()->create(['status' => 'active']);
        $plan = Plan::factory()->starter()->create();
        $adminUser = User::factory()->admin()->create(['tenant_id' => $tenant->id]);

        // Create expired subscription
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'ends_at' => now()->subDay(),
        ]);

        $subscriptionService = app(SubscriptionService::class);

        // Act - Check expiry
        $subscriptionService->checkExpiry();

        // Assert - Subscription marked as expired
        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $tenant->id,
            'status' => 'expired',
        ]);

        // Assert - Tenant status updated
        $tenant->refresh();
        $this->assertEquals('expired', $tenant->status);
    }

    public function test_quota_service_thread_safe_decrement(): void
    {
        // Arrange
        $tenant = Tenant::factory()->create(['status' => 'active']);
        $plan = Plan::factory()->starter()->create(['message_quota' => 10]);
        Subscription::factory()->active()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'message_quota_used' => 0,
            'message_quota_limit' => 10,
        ]);

        $quotaService = app(QuotaService::class);

        // Act - Decrement quota multiple times
        for ($i = 0; $i < 5; $i++) {
            $quotaService->decrement($tenant);
        }

        // Assert - Quota correctly decremented
        $this->assertEquals(5, $quotaService->getRemainingQuota($tenant));

        // Assert - Database reflects correct usage
        $subscription = $tenant->subscriptions()->where('status', 'active')->first();
        $this->assertEquals(5, $subscription->message_quota_used);
    }

    public function test_unlimited_quota_for_business_plan(): void
    {
        // Arrange
        $tenant = Tenant::factory()->create(['status' => 'active']);
        $plan = Plan::factory()->business()->create();
        Subscription::factory()->active()->unlimited()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
        ]);

        $quotaService = app(QuotaService::class);

        // Assert - Unlimited quota returns -1
        $this->assertEquals(-1, $quotaService->getRemainingQuota($tenant));
        $this->assertTrue($quotaService->isUnlimited($tenant));
        $this->assertFalse($quotaService->isExhausted($tenant));

        // Act - Decrement should not throw for unlimited
        $quotaService->decrement($tenant);

        // Assert - Still unlimited
        $this->assertTrue($quotaService->isUnlimited($tenant));
    }
}
