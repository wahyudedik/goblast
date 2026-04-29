<?php

namespace Tests\Feature\Api\V1;

use App\Models\Device;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Template;
use App\Models\Tenant;
use App\Services\ApiTokenService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SendBulkTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Tenant $tenant;

    private Plan $plan;

    private Subscription $subscription;

    private Device $device;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $this->plan = Plan::factory()->business()->create();
        $this->tenant = Tenant::factory()->create();
        $this->subscription = Subscription::factory()
            ->for($this->tenant)
            ->for($this->plan)
            ->active()
            ->create();
        $this->device = Device::factory()
            ->connected()
            ->for($this->tenant)
            ->create();

        $service = new ApiTokenService;
        $result = $service->generate($this->tenant, 'Test Token');
        $this->token = $result['token'];
    }

    public function test_send_bulk_returns_202_with_valid_parameters(): void
    {
        $response = $this->postJson('/api/v1/send-bulk', [
            'device_id' => $this->device->id,
            'recipients' => ['6281234567890', '6281234567891'],
            'message' => 'Hello, this is a bulk test message',
        ], [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(202);
        $response->assertJsonStructure([
            'success',
            'broadcast_id',
            'total_recipients',
            'status',
            'message',
        ]);
        $response->assertJson([
            'success' => true,
            'status' => 'queued',
        ]);
    }

    public function test_send_bulk_creates_broadcast_record(): void
    {
        $this->postJson('/api/v1/send-bulk', [
            'device_id' => $this->device->id,
            'recipients' => ['6281234567890', '6281234567891'],
            'message' => 'Bulk message test',
        ], [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $this->assertDatabaseHas('broadcasts', [
            'tenant_id' => $this->tenant->id,
            'device_id' => $this->device->id,
            'source_type' => 'database',
        ]);
    }

    public function test_send_bulk_creates_message_logs_for_each_recipient(): void
    {
        $recipients = ['6281234567890', '6281234567891', '6281234567892'];

        $this->postJson('/api/v1/send-bulk', [
            'device_id' => $this->device->id,
            'recipients' => $recipients,
            'message' => 'Bulk message test',
        ], [
            'Authorization' => "Bearer {$this->token}",
        ]);

        foreach ($recipients as $recipient) {
            $this->assertDatabaseHas('message_logs', [
                'tenant_id' => $this->tenant->id,
                'device_id' => $this->device->id,
                'recipient' => $recipient,
                'status' => 'pending',
                'source' => 'broadcast',
            ]);
        }
    }

    public function test_send_bulk_returns_correct_total_recipients(): void
    {
        $recipients = ['6281234567890', '6281234567891'];

        $response = $this->postJson('/api/v1/send-bulk', [
            'device_id' => $this->device->id,
            'recipients' => $recipients,
            'message' => 'Bulk message test',
        ], [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(202);
        $response->assertJsonFragment([
            'total_recipients' => 2,
        ]);
    }

    public function test_send_bulk_with_template(): void
    {
        $template = Template::factory()
            ->noVariables()
            ->for($this->tenant)
            ->create(['content' => 'Template bulk message']);

        $response = $this->postJson('/api/v1/send-bulk', [
            'device_id' => $this->device->id,
            'recipients' => ['6281234567890'],
            'template_id' => $template->id,
            'message' => null,
        ], [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(202);
        $response->assertJson([
            'success' => true,
            'status' => 'queued',
        ]);
    }

    public function test_returns_401_without_authorization_header(): void
    {
        $response = $this->postJson('/api/v1/send-bulk', [
            'device_id' => $this->device->id,
            'recipients' => ['6281234567890'],
            'message' => 'Hello',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'error' => 'Unauthorized',
            'message' => 'Token tidak valid atau tidak ditemukan',
        ]);
    }

    public function test_returns_401_with_invalid_token(): void
    {
        $response = $this->postJson('/api/v1/send-bulk', [
            'device_id' => $this->device->id,
            'recipients' => ['6281234567890'],
            'message' => 'Hello',
        ], [
            'Authorization' => 'Bearer invalid-token',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'error' => 'Unauthorized',
        ]);
    }

    public function test_returns_422_with_missing_required_fields(): void
    {
        $response = $this->postJson('/api/v1/send-bulk', [], [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['device_id', 'recipients']);
    }

    public function test_returns_422_with_empty_recipients_array(): void
    {
        $response = $this->postJson('/api/v1/send-bulk', [
            'device_id' => $this->device->id,
            'recipients' => [],
            'message' => 'Hello',
        ], [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['recipients']);
    }

    public function test_returns_422_with_invalid_phone_number_in_recipients(): void
    {
        $response = $this->postJson('/api/v1/send-bulk', [
            'device_id' => $this->device->id,
            'recipients' => ['not-a-phone-number'],
            'message' => 'Hello',
        ], [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['recipients.0']);
    }

    public function test_returns_422_with_nonexistent_device(): void
    {
        $response = $this->postJson('/api/v1/send-bulk', [
            'device_id' => 99999,
            'recipients' => ['6281234567890'],
            'message' => 'Hello',
        ], [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['device_id']);
    }

    public function test_returns_422_when_device_belongs_to_another_tenant(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherDevice = Device::factory()
            ->connected()
            ->for($otherTenant)
            ->create();

        $response = $this->postJson('/api/v1/send-bulk', [
            'device_id' => $otherDevice->id,
            'recipients' => ['6281234567890'],
            'message' => 'Hello',
        ], [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'device_id' => ['Device tidak ditemukan atau bukan milik tenant Anda.'],
        ]);
    }

    public function test_returns_422_when_device_is_not_connected(): void
    {
        $disconnectedDevice = Device::factory()
            ->disconnected()
            ->for($this->tenant)
            ->create();

        $response = $this->postJson('/api/v1/send-bulk', [
            'device_id' => $disconnectedDevice->id,
            'recipients' => ['6281234567890'],
            'message' => 'Hello',
        ], [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'device_id' => ['Device tidak dalam status terhubung. Status saat ini: disconnected'],
        ]);
    }

    public function test_returns_422_when_template_belongs_to_another_tenant(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherTemplate = Template::factory()
            ->for($otherTenant)
            ->create();

        $response = $this->postJson('/api/v1/send-bulk', [
            'device_id' => $this->device->id,
            'recipients' => ['6281234567890'],
            'template_id' => $otherTemplate->id,
            'message' => 'Hello',
        ], [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'template_id' => ['Template tidak ditemukan atau bukan milik tenant Anda.'],
        ]);
    }

    public function test_returns_403_when_subscription_is_inactive(): void
    {
        $this->subscription->update([
            'status' => 'expired',
            'ends_at' => now()->subDay(),
        ]);

        $response = $this->postJson('/api/v1/send-bulk', [
            'device_id' => $this->device->id,
            'recipients' => ['6281234567890'],
            'message' => 'Hello',
        ], [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(403);
    }

    public function test_returns_403_when_plan_does_not_have_api_feature(): void
    {
        $starterPlan = Plan::factory()->starter()->create();
        $this->subscription->update(['plan_id' => $starterPlan->id]);

        $response = $this->postJson('/api/v1/send-bulk', [
            'device_id' => $this->device->id,
            'recipients' => ['6281234567890'],
            'message' => 'Hello',
        ], [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(403);
    }

    public function test_returns_422_when_message_is_missing_and_no_template(): void
    {
        $response = $this->postJson('/api/v1/send-bulk', [
            'device_id' => $this->device->id,
            'recipients' => ['6281234567890'],
        ], [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['message']);
    }

    public function test_returns_422_with_nonexistent_template(): void
    {
        $response = $this->postJson('/api/v1/send-bulk', [
            'device_id' => $this->device->id,
            'recipients' => ['6281234567890'],
            'template_id' => 99999,
            'message' => 'Hello',
        ], [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['template_id']);
    }

    public function test_send_bulk_with_plus_prefix_phone_numbers(): void
    {
        $response = $this->postJson('/api/v1/send-bulk', [
            'device_id' => $this->device->id,
            'recipients' => ['+6281234567890', '+6281234567891'],
            'message' => 'Hello with plus prefix',
        ], [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(202);
        $response->assertJson([
            'success' => true,
            'status' => 'queued',
        ]);
    }

    public function test_returns_422_with_recipients_not_an_array(): void
    {
        $response = $this->postJson('/api/v1/send-bulk', [
            'device_id' => $this->device->id,
            'recipients' => '6281234567890',
            'message' => 'Hello',
        ], [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['recipients']);
    }

    public function test_send_bulk_decrements_quota_for_each_recipient(): void
    {
        $this->subscription->update([
            'message_quota_used' => 0,
            'message_quota_limit' => 100,
        ]);

        $recipients = ['6281234567890', '6281234567891', '6281234567892'];

        $this->postJson('/api/v1/send-bulk', [
            'device_id' => $this->device->id,
            'recipients' => $recipients,
            'message' => 'Hello',
        ], [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $this->subscription->refresh();
        $this->assertEquals(3, $this->subscription->message_quota_used);
    }
}
