<?php

namespace Tests\Unit;

use App\Jobs\SendMessageJob;
use App\Models\AutoReplyCooldown;
use App\Models\Device;
use App\Models\KeywordRule;
use App\Models\Tenant;
use App\Services\AutoReplyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AutoReplyServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AutoReplyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AutoReplyService;
    }

    public function test_matches_keyword_case_insensitive(): void
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create(['tenant_id' => $tenant->id]);
        $keywordRule = KeywordRule::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'keyword' => 'HARGA',
            'reply' => 'Harga mulai dari Rp 100.000',
            'is_active' => true,
            'priority' => 1,
        ]);

        // Act
        $matched = $this->service->matchKeyword($device, 'berapa harga produknya?');

        // Assert
        $this->assertNotNull($matched);
        $this->assertEquals($keywordRule->id, $matched->id);
    }

    public function test_returns_null_when_no_keyword_matches(): void
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create(['tenant_id' => $tenant->id]);
        KeywordRule::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'keyword' => 'harga',
            'reply' => 'Harga mulai dari Rp 100.000',
            'is_active' => true,
        ]);

        // Act
        $matched = $this->service->matchKeyword($device, 'lokasi toko dimana?');

        // Assert
        $this->assertNull($matched);
    }

    public function test_selects_highest_priority_keyword_when_multiple_match(): void
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create(['tenant_id' => $tenant->id]);

        $lowPriority = KeywordRule::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'keyword' => 'info',
            'reply' => 'Low priority reply',
            'is_active' => true,
            'priority' => 1,
        ]);

        $highPriority = KeywordRule::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'keyword' => 'harga',
            'reply' => 'High priority reply',
            'is_active' => true,
            'priority' => 10,
        ]);

        // Act - message contains both keywords
        $matched = $this->service->matchKeyword($device, 'berapa harga dan info?');

        // Assert - should match highest priority first
        $this->assertNotNull($matched);
        $this->assertEquals($highPriority->id, $matched->id);
    }

    public function test_can_reply_returns_true_when_no_cooldown(): void
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create([
            'tenant_id' => $tenant->id,
            'gateway_device_id' => 'test-device-123',
        ]);
        $keywordRule = KeywordRule::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'keyword' => 'harga',
        ]);

        // Act
        $canReply = $this->service->canReply('test-device-123', '6281234567890', 'harga');

        // Assert
        $this->assertTrue($canReply);
    }

    public function test_can_reply_returns_false_when_cooldown_active(): void
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create([
            'tenant_id' => $tenant->id,
            'gateway_device_id' => 'test-device-123',
        ]);
        $keywordRule = KeywordRule::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'keyword' => 'harga',
        ]);

        // Create active cooldown
        AutoReplyCooldown::create([
            'device_id' => $device->id,
            'keyword_rule_id' => $keywordRule->id,
            'from' => '6281234567890',
            'expires_at' => now()->addMinutes(30),
        ]);

        // Act
        $canReply = $this->service->canReply('test-device-123', '6281234567890', 'harga');

        // Assert
        $this->assertFalse($canReply);
    }

    public function test_process_incoming_message_creates_auto_reply_log(): void
    {
        // Arrange
        Queue::fake();

        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create([
            'tenant_id' => $tenant->id,
            'gateway_device_id' => 'test-device-123',
        ]);
        $keywordRule = KeywordRule::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'keyword' => 'harga',
            'reply' => 'Harga mulai dari Rp 100.000',
            'is_active' => true,
        ]);

        // Act
        $this->service->processIncomingMessage('test-device-123', '6281234567890', 'berapa harga?');

        // Assert
        $this->assertDatabaseHas('auto_reply_logs', [
            'device_id' => $device->id,
            'from' => '6281234567890',
            'message' => 'berapa harga?',
            'matched' => true,
            'reply_sent' => true,
        ]);
    }

    public function test_process_incoming_message_dispatches_send_message_job(): void
    {
        // Arrange
        Queue::fake();

        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create([
            'tenant_id' => $tenant->id,
            'gateway_device_id' => 'test-device-123',
        ]);
        $keywordRule = KeywordRule::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'keyword' => 'harga',
            'reply' => 'Harga mulai dari Rp 100.000',
            'is_active' => true,
        ]);

        // Act
        $this->service->processIncomingMessage('test-device-123', '6281234567890', 'berapa harga?');

        // Assert
        Queue::assertPushed(SendMessageJob::class, function ($job) {
            return $job->messageLog->recipient === '6281234567890'
                && $job->messageLog->message === 'Harga mulai dari Rp 100.000'
                && $job->messageLog->source === 'auto_reply';
        });
    }

    public function test_process_incoming_message_sets_cooldown(): void
    {
        // Arrange
        Queue::fake();

        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create([
            'tenant_id' => $tenant->id,
            'gateway_device_id' => 'test-device-123',
        ]);
        $keywordRule = KeywordRule::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'keyword' => 'harga',
            'reply' => 'Harga mulai dari Rp 100.000',
            'is_active' => true,
        ]);

        // Act
        $this->service->processIncomingMessage('test-device-123', '6281234567890', 'berapa harga?');

        // Assert
        $this->assertDatabaseHas('auto_reply_cooldowns', [
            'device_id' => $device->id,
            'keyword_rule_id' => $keywordRule->id,
            'from' => '6281234567890',
        ]);
    }

    public function test_process_incoming_message_skips_reply_when_cooldown_active(): void
    {
        // Arrange
        Queue::fake();

        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create([
            'tenant_id' => $tenant->id,
            'gateway_device_id' => 'test-device-123',
        ]);
        $keywordRule = KeywordRule::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'keyword' => 'harga',
            'reply' => 'Harga mulai dari Rp 100.000',
            'is_active' => true,
        ]);

        // Create active cooldown
        AutoReplyCooldown::create([
            'device_id' => $device->id,
            'keyword_rule_id' => $keywordRule->id,
            'from' => '6281234567890',
            'expires_at' => now()->addMinutes(30),
        ]);

        // Act
        $this->service->processIncomingMessage('test-device-123', '6281234567890', 'berapa harga?');

        // Assert
        Queue::assertNothingPushed();

        $this->assertDatabaseHas('auto_reply_logs', [
            'device_id' => $device->id,
            'from' => '6281234567890',
            'matched' => true,
            'reply_sent' => false,
        ]);
    }

    public function test_process_incoming_message_logs_unmatched_message(): void
    {
        // Arrange
        Queue::fake();

        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create([
            'tenant_id' => $tenant->id,
            'gateway_device_id' => 'test-device-123',
        ]);

        // Act
        $this->service->processIncomingMessage('test-device-123', '6281234567890', 'random message');

        // Assert
        $this->assertDatabaseHas('auto_reply_logs', [
            'device_id' => $device->id,
            'from' => '6281234567890',
            'message' => 'random message',
            'matched' => false,
            'reply_sent' => false,
        ]);

        Queue::assertNothingPushed();
    }

    public function test_process_incoming_message_cleans_up_expired_cooldowns(): void
    {
        // Arrange
        Queue::fake();

        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create([
            'tenant_id' => $tenant->id,
            'gateway_device_id' => 'test-device-123',
        ]);
        $keywordRule = KeywordRule::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'keyword' => 'harga',
            'reply' => 'Harga mulai dari Rp 100.000',
            'is_active' => true,
        ]);

        // Create expired cooldown
        AutoReplyCooldown::create([
            'device_id' => $device->id,
            'keyword_rule_id' => $keywordRule->id,
            'from' => '6281234567890',
            'expires_at' => now()->subMinutes(10),
        ]);

        $this->assertDatabaseCount('auto_reply_cooldowns', 1);

        // Act
        $this->service->processIncomingMessage('test-device-123', '6281234567890', 'berapa harga?');

        // Assert - expired cooldown should be cleaned up, new one created for the reply
        $this->assertDatabaseCount('auto_reply_cooldowns', 1);
        $this->assertDatabaseHas('auto_reply_cooldowns', [
            'device_id' => $device->id,
            'keyword_rule_id' => $keywordRule->id,
            'from' => '6281234567890',
        ]);

        // Reply should have been sent since the old cooldown was expired
        Queue::assertPushed(SendMessageJob::class);
    }

    public function test_can_reply_returns_false_for_unknown_device(): void
    {
        $this->assertFalse($this->service->canReply('nonexistent-device', '6281234567890', 'harga'));
    }

    public function test_can_reply_returns_false_for_unknown_keyword(): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create([
            'tenant_id' => $tenant->id,
            'gateway_device_id' => 'test-device-123',
        ]);

        $this->assertFalse($this->service->canReply('test-device-123', '6281234567890', 'nonexistent'));
    }

    public function test_process_incoming_message_ignores_unknown_device(): void
    {
        Queue::fake();

        $this->service->processIncomingMessage('nonexistent-device', '6281234567890', 'harga');

        $this->assertDatabaseCount('auto_reply_logs', 0);
        Queue::assertNothingPushed();
    }

    public function test_match_keyword_ignores_inactive_rules(): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create(['tenant_id' => $tenant->id]);
        KeywordRule::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'keyword' => 'harga',
            'reply' => 'Reply',
            'is_active' => false,
        ]);

        $this->assertNull($this->service->matchKeyword($device, 'berapa harga?'));
    }

    public function test_can_reply_returns_true_when_cooldown_expired(): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create([
            'tenant_id' => $tenant->id,
            'gateway_device_id' => 'test-device-123',
        ]);
        $keywordRule = KeywordRule::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'keyword' => 'harga',
        ]);

        // Create expired cooldown
        AutoReplyCooldown::create([
            'device_id' => $device->id,
            'keyword_rule_id' => $keywordRule->id,
            'from' => '6281234567890',
            'expires_at' => now()->subMinutes(1),
        ]);

        $this->assertTrue($this->service->canReply('test-device-123', '6281234567890', 'harga'));
    }
}
