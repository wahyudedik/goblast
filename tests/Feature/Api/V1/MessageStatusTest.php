<?php

namespace Tests\Feature\Api\V1;

use App\Models\Device;
use App\Models\MessageLog;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\ApiTokenService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class MessageStatusTest extends TestCase
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

    public function test_returns_200_with_sent_message_status(): void
    {
        $messageLog = MessageLog::factory()
            ->sent()
            ->for($this->tenant)
            ->for($this->device)
            ->create();

        $response = $this->getJson("/api/v1/message-status/{$messageLog->job_id}", [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'job_id',
            'status',
            'recipient',
            'sent_at',
            'failed_at',
            'error_message',
            'attempts',
            'message',
        ]);
        $response->assertJson([
            'job_id' => $messageLog->job_id,
            'status' => 'sent',
            'recipient' => $messageLog->recipient,
            'message' => 'Pesan berhasil terkirim',
        ]);
        $response->assertJsonMissing(['error']);
    }

    public function test_returns_200_with_pending_message_status(): void
    {
        $messageLog = MessageLog::factory()
            ->pending()
            ->for($this->tenant)
            ->for($this->device)
            ->create();

        $response = $this->getJson("/api/v1/message-status/{$messageLog->job_id}", [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'pending',
            'message' => 'Pesan sedang menunggu dalam antrian',
        ]);
    }

    public function test_returns_200_with_failed_message_status(): void
    {
        $messageLog = MessageLog::factory()
            ->failed()
            ->for($this->tenant)
            ->for($this->device)
            ->create();

        $response = $this->getJson("/api/v1/message-status/{$messageLog->job_id}", [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'failed',
            'message' => 'Pesan gagal terkirim',
        ]);
        $response->assertJsonStructure(['failed_at', 'error_message']);
    }

    public function test_returns_200_with_cancelled_message_status(): void
    {
        $messageLog = MessageLog::factory()
            ->cancelled()
            ->for($this->tenant)
            ->for($this->device)
            ->create();

        $response = $this->getJson("/api/v1/message-status/{$messageLog->job_id}", [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'cancelled',
            'message' => 'Pesan dibatalkan',
        ]);
    }

    public function test_returns_200_with_retrying_message_status(): void
    {
        $messageLog = MessageLog::factory()
            ->retrying()
            ->for($this->tenant)
            ->for($this->device)
            ->create();

        $response = $this->getJson("/api/v1/message-status/{$messageLog->job_id}", [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'retrying',
            'message' => 'Pesan sedang dicoba ulang',
        ]);
    }

    public function test_returns_404_with_nonexistent_job_id(): void
    {
        $response = $this->getJson('/api/v1/message-status/nonexistent-job-id', [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'error' => 'Not found',
            'message' => 'Job tidak ditemukan',
        ]);
    }

    public function test_returns_404_when_job_belongs_to_another_tenant(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherDevice = Device::factory()
            ->connected()
            ->for($otherTenant)
            ->create();

        $messageLog = MessageLog::factory()
            ->sent()
            ->for($otherTenant)
            ->for($otherDevice)
            ->create();

        $response = $this->getJson("/api/v1/message-status/{$messageLog->job_id}", [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'error' => 'Not found',
            'message' => 'Job tidak ditemukan',
        ]);
    }

    public function test_returns_401_without_authorization_header(): void
    {
        $messageLog = MessageLog::factory()
            ->sent()
            ->for($this->tenant)
            ->for($this->device)
            ->create();

        $response = $this->getJson("/api/v1/message-status/{$messageLog->job_id}");

        $response->assertStatus(401);
        $response->assertJson([
            'error' => 'Unauthorized',
            'message' => 'Token tidak valid atau tidak ditemukan',
        ]);
    }

    public function test_returns_401_with_invalid_token(): void
    {
        $messageLog = MessageLog::factory()
            ->sent()
            ->for($this->tenant)
            ->for($this->device)
            ->create();

        $response = $this->getJson("/api/v1/message-status/{$messageLog->job_id}", [
            'Authorization' => 'Bearer invalid-token',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'error' => 'Unauthorized',
        ]);
    }

    public function test_sent_at_is_null_for_pending_message(): void
    {
        $messageLog = MessageLog::factory()
            ->pending()
            ->for($this->tenant)
            ->for($this->device)
            ->create();

        $response = $this->getJson("/api/v1/message-status/{$messageLog->job_id}", [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'sent_at' => null,
            'failed_at' => null,
        ]);
    }
}
