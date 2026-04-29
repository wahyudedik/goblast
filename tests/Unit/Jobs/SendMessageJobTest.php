<?php

namespace Tests\Unit\Jobs;

use App\Jobs\SendMessageJob;
use App\Models\Alert;
use App\Models\Device;
use App\Models\MessageLog;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\Contracts\BaileysGatewayClientInterface;
use App\Services\ValueObjects\BaileysResponse;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendMessageJobTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sends_message_successfully(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create();
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);
        $device = Device::factory()->create(['tenant_id' => $tenant->id]);
        $messageLog = MessageLog::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'status' => 'pending',
        ]);

        $gatewayClient = $this->mock(BaileysGatewayClientInterface::class);
        $gatewayClient->shouldReceive('sendMessage')
            ->once()
            ->with($device->gateway_device_id, $messageLog->recipient, $messageLog->message)
            ->andReturn(new BaileysResponse(
                success: true,
                status: 'sent',
                messageId: 'msg-123'
            ));

        $job = new SendMessageJob($messageLog);
        $job->handle($gatewayClient);

        $messageLog->refresh();
        $this->assertEquals('sent', $messageLog->status);
        $this->assertNotNull($messageLog->sent_at);
    }

    #[Test]
    public function it_marks_message_as_cancelled_when_subscription_inactive(): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create(['tenant_id' => $tenant->id]);
        $messageLog = MessageLog::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'status' => 'pending',
        ]);

        $gatewayClient = $this->mock(BaileysGatewayClientInterface::class);
        $gatewayClient->shouldNotReceive('sendMessage');

        $job = new SendMessageJob($messageLog);
        $job->handle($gatewayClient);

        $messageLog->refresh();
        $this->assertEquals('cancelled', $messageLog->status);
    }

    #[Test]
    public function it_retries_on_gateway_error(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create();
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);
        $device = Device::factory()->create(['tenant_id' => $tenant->id]);
        $messageLog = MessageLog::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'status' => 'pending',
        ]);

        $gatewayClient = $this->mock(BaileysGatewayClientInterface::class);
        $gatewayClient->shouldReceive('sendMessage')
            ->once()
            ->andThrow(new \Exception('Gateway error'));

        $job = new SendMessageJob($messageLog);

        $this->expectException(\Exception::class);
        $job->handle($gatewayClient);

        $messageLog->refresh();
        $this->assertEquals('retrying', $messageLog->status);
        $this->assertEquals(1, $messageLog->attempts);
    }

    #[Test]
    public function it_marks_as_failed_after_max_retries(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create();
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);
        $device = Device::factory()->create(['tenant_id' => $tenant->id]);
        $messageLog = MessageLog::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'status' => 'pending',
        ]);

        $gatewayClient = $this->mock(BaileysGatewayClientInterface::class);
        $gatewayClient->shouldReceive('sendMessage')
            ->andThrow(new \Exception('Gateway error'));

        $job = new SendMessageJob($messageLog);

        // Call failed() directly to simulate max retries exceeded
        $exception = new \Exception('Gateway error');
        $job->failed($exception);

        $messageLog->refresh();
        $this->assertEquals('failed', $messageLog->status);
        $this->assertNotNull($messageLog->failed_at);
    }

    #[Test]
    public function it_handles_failed_job_callback(): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create(['tenant_id' => $tenant->id]);
        $messageLog = MessageLog::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'status' => 'pending',
        ]);

        $job = new SendMessageJob($messageLog);
        $exception = new \Exception('Test error');

        $job->failed($exception);

        $messageLog->refresh();
        $this->assertEquals('failed', $messageLog->status);
        $this->assertNotNull($messageLog->failed_at);
        $this->assertEquals('Test error', $messageLog->error_message);
    }

    #[Test]
    public function it_stores_error_message_on_failure(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create();
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);
        $device = Device::factory()->create(['tenant_id' => $tenant->id]);
        $messageLog = MessageLog::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'status' => 'pending',
        ]);

        $gatewayClient = $this->mock(BaileysGatewayClientInterface::class);
        $gatewayClient->shouldReceive('sendMessage')
            ->andThrow(new \Exception('Connection timeout'));

        $job = new SendMessageJob($messageLog);

        try {
            $job->handle($gatewayClient);
        } catch (\Exception $e) {
            // Expected
        }

        $messageLog->refresh();
        $this->assertStringContainsString('Connection timeout', $messageLog->error_message);
    }

    #[Test]
    public function it_handles_gateway_error_response(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create();
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);
        $device = Device::factory()->create(['tenant_id' => $tenant->id]);
        $messageLog = MessageLog::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'status' => 'pending',
        ]);

        $gatewayClient = $this->mock(BaileysGatewayClientInterface::class);
        $gatewayClient->shouldReceive('sendMessage')
            ->once()
            ->andReturn(new BaileysResponse(
                success: false,
                status: 'failed',
                errorMessage: 'Device not connected'
            ));

        $job = new SendMessageJob($messageLog);

        $this->expectException(\Exception::class);
        $job->handle($gatewayClient);

        $messageLog->refresh();
        $this->assertEquals('retrying', $messageLog->status);
    }

    #[Test]
    public function it_creates_alert_on_permanent_failure(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create();
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);
        $device = Device::factory()->create(['tenant_id' => $tenant->id]);
        $messageLog = MessageLog::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'status' => 'pending',
        ]);

        $gatewayClient = $this->mock(BaileysGatewayClientInterface::class);
        $gatewayClient->shouldReceive('sendMessage')
            ->andThrow(new \Exception('Gateway error'));

        $job = new SendMessageJob($messageLog);

        // Simulate max retries by setting attempts to 3
        $reflection = new \ReflectionClass($job);
        $property = $reflection->getProperty('job');
        $property->setAccessible(true);

        $mockJob = $this->createMock(Job::class);
        $mockJob->method('attempts')->willReturn(3);
        $property->setValue($job, $mockJob);

        try {
            $job->handle($gatewayClient);
        } catch (\Exception $e) {
            // Expected
        }

        $messageLog->refresh();
        $this->assertEquals('failed', $messageLog->status);
        $this->assertNotNull($messageLog->failed_at);

        // Verify alert was created
        $this->assertDatabaseHas('alerts', [
            'tenant_id' => $tenant->id,
            'type' => 'job.failed_permanent',
            'severity' => 'error',
            'status' => 'active',
        ]);

        $alert = Alert::where('tenant_id', $tenant->id)->first();
        $this->assertStringContainsString($messageLog->recipient, $alert->message);
        $this->assertEquals($messageLog->id, $alert->context['message_log_id']);
    }
}
