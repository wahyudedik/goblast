<?php

namespace Tests\Unit\Services;

use App\Exceptions\QuotaExceededException;
use App\Jobs\SendMessageJob;
use App\Models\Device;
use App\Models\MessageLog;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Template;
use App\Models\Tenant;
use App\Services\MessageService;
use App\Services\QuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MessageServiceTest extends TestCase
{
    use RefreshDatabase;

    private MessageService $messageService;

    private QuotaService $quotaService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->quotaService = new QuotaService;
        $this->messageService = new MessageService($this->quotaService);
    }

    #[Test]
    public function it_sends_a_single_message_successfully(): void
    {
        Queue::fake();

        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['message_quota' => 100]);
        $subscription = Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'message_quota_limit' => 100,
            'message_quota_used' => 0,
        ]);
        $device = Device::factory()->create(['tenant_id' => $tenant->id]);

        $messageLog = $this->messageService->sendSingle(
            $device,
            '6281234567890',
            'Test message'
        );

        $this->assertDatabaseHas('message_logs', [
            'id' => $messageLog->id,
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'recipient' => '6281234567890',
            'message' => 'Test message',
            'status' => 'pending',
            'source' => 'api',
        ]);

        Queue::assertPushed(SendMessageJob::class);
    }

    #[Test]
    public function it_validates_phone_number_format(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['message_quota' => 100]);
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'message_quota_limit' => 100,
        ]);
        $device = Device::factory()->create(['tenant_id' => $tenant->id]);

        $this->expectException(\InvalidArgumentException::class);

        $this->messageService->sendSingle($device, 'invalid-phone', 'Test message');
    }

    #[Test]
    public function it_normalizes_phone_number_format(): void
    {
        Queue::fake();

        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['message_quota' => 100]);
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'message_quota_limit' => 100,
        ]);
        $device = Device::factory()->create(['tenant_id' => $tenant->id]);

        $messageLog = $this->messageService->sendSingle(
            $device,
            '+62 (812) 345-67890',
            'Test message'
        );

        $this->assertEquals('6281234567890', $messageLog->recipient);
    }

    #[Test]
    public function it_throws_exception_when_quota_exhausted(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['message_quota' => 0]);
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'message_quota_limit' => 0,
            'message_quota_used' => 0,
        ]);
        $device = Device::factory()->create(['tenant_id' => $tenant->id]);

        $this->expectException(QuotaExceededException::class);

        $this->messageService->sendSingle($device, '6281234567890', 'Test message');
    }

    #[Test]
    public function it_decrements_quota_after_sending(): void
    {
        Queue::fake();

        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['message_quota' => 100]);
        $subscription = Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'message_quota_limit' => 100,
            'message_quota_used' => 0,
        ]);
        $device = Device::factory()->create(['tenant_id' => $tenant->id]);

        $this->messageService->sendSingle($device, '6281234567890', 'Test message');

        $subscription->refresh();
        $this->assertEquals(1, $subscription->message_quota_used);
    }

    #[Test]
    public function it_renders_template_with_variables(): void
    {
        $tenant = Tenant::factory()->create();
        $template = Template::factory()->create([
            'tenant_id' => $tenant->id,
            'content' => 'Hello {name}, your status is {status}',
        ]);

        $rendered = $this->messageService->renderTemplate($template, [
            'name' => 'John',
            'status' => 'active',
        ]);

        $this->assertEquals('Hello John, your status is active', $rendered);
    }

    #[Test]
    public function it_handles_missing_template_variables_gracefully(): void
    {
        $tenant = Tenant::factory()->create();
        $template = Template::factory()->create([
            'tenant_id' => $tenant->id,
            'content' => 'Hello {name}, your status is {status}',
        ]);

        $rendered = $this->messageService->renderTemplate($template, [
            'name' => 'John',
        ]);

        $this->assertEquals('Hello John, your status is ', $rendered);
    }

    #[Test]
    public function it_logs_warning_for_missing_template_variables(): void
    {
        $tenant = Tenant::factory()->create();
        $template = Template::factory()->create([
            'tenant_id' => $tenant->id,
            'content' => 'Hello {name}, your status is {status}',
        ]);

        \Log::shouldReceive('warning')
            ->once()
            ->with('Template variable missing', \Mockery::on(function ($context) {
                return $context['variable'] === 'status';
            }));

        $this->messageService->renderTemplate($template, [
            'name' => 'John',
        ]);
    }

    #[Test]
    public function it_sends_message_with_template(): void
    {
        Queue::fake();

        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['message_quota' => 100]);
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'message_quota_limit' => 100,
        ]);
        $device = Device::factory()->create(['tenant_id' => $tenant->id]);
        $template = Template::factory()->create([
            'tenant_id' => $tenant->id,
            'content' => 'Hello {name}',
        ]);

        $messageLog = $this->messageService->sendSingle(
            $device,
            '6281234567890',
            'Hello {name}',
            $template
        );

        $this->assertEquals($template->id, $messageLog->template_id);
    }

    #[Test]
    public function it_dispatches_job_without_delay(): void
    {
        Queue::fake();

        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['message_quota' => 100]);
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'message_quota_limit' => 100,
        ]);
        $device = Device::factory()->create(['tenant_id' => $tenant->id]);
        $messageLog = MessageLog::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
        ]);

        $this->messageService->dispatchJob($messageLog);

        Queue::assertPushed(SendMessageJob::class);
    }

    #[Test]
    public function it_dispatches_job_with_delay(): void
    {
        Queue::fake();

        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create(['tenant_id' => $tenant->id]);
        $messageLog = MessageLog::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
        ]);

        $this->messageService->dispatchJob($messageLog, 10);

        Queue::assertPushed(SendMessageJob::class, function ($job) {
            return $job->delay !== null;
        });
    }

    #[Test]
    public function it_does_not_send_when_no_active_subscription(): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create(['tenant_id' => $tenant->id]);

        $this->expectException(QuotaExceededException::class);

        $this->messageService->sendSingle($device, '6281234567890', 'Test message');
    }

    #[Test]
    public function it_allows_unlimited_quota(): void
    {
        Queue::fake();

        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['message_quota' => null]); // unlimited
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'message_quota_limit' => null,
            'message_quota_used' => 0,
        ]);
        $device = Device::factory()->create(['tenant_id' => $tenant->id]);

        // Should not throw exception even with many messages
        for ($i = 0; $i < 5; $i++) {
            $this->messageService->sendSingle($device, '6281234567890', 'Test message');
        }

        $this->assertTrue(true);
    }
}
