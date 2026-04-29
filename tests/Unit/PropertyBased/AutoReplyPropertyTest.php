<?php

namespace Tests\Unit\PropertyBased;

use App\Jobs\SendMessageJob;
use App\Models\AutoReplyCooldown;
use App\Models\Device;
use App\Models\KeywordRule;
use App\Models\Tenant;
use App\Services\AutoReplyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-based tests for AutoReplyService correctness properties.
 *
 * These tests verify the following correctness properties:
 * 1. Keyword matching is case-insensitive
 * 2. Cooldown prevents duplicate replies
 * 3. Priority selection is deterministic
 */
class AutoReplyPropertyTest extends TestCase
{
    use RefreshDatabase;

    private AutoReplyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AutoReplyService;
        Queue::fake();
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PROPERTY 1: Keyword matching is case-insensitive
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Property: matchKeyword(message) = matchKeyword(lowercase(message)) = matchKeyword(uppercase(message)).
     *
     * For any keyword rule and message containing that keyword,
     * the match result is the same regardless of case.
     */
    #[Test]
    #[DataProvider('caseVariations')]
    public function keyword_matching_is_case_insensitive(
        string $keyword,
        string $messageVariation,
        bool $shouldMatch
    ): void {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create(['tenant_id' => $tenant->id]);

        $keywordRule = KeywordRule::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'keyword' => $keyword,
            'reply' => 'Test reply',
            'is_active' => true,
            'priority' => 1,
        ]);

        $matched = $this->service->matchKeyword($device, $messageVariation);

        if ($shouldMatch) {
            $this->assertNotNull($matched, "Message '{$messageVariation}' should match keyword '{$keyword}'");
            $this->assertEquals($keywordRule->id, $matched->id);
        } else {
            $this->assertNull($matched, "Message '{$messageVariation}' should not match keyword '{$keyword}'");
        }
    }

    /**
     * Property: For any keyword K, message containing K in any case matches.
     */
    #[Test]
    #[DataProvider('keywordCasePermutations')]
    public function all_case_permutations_match(string $keyword, array $messageVariations): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create(['tenant_id' => $tenant->id]);

        KeywordRule::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'keyword' => $keyword,
            'reply' => 'Test reply',
            'is_active' => true,
        ]);

        foreach ($messageVariations as $message) {
            $matched = $this->service->matchKeyword($device, $message);

            // Property: All case variations should match
            $this->assertNotNull(
                $matched,
                "Message '{$message}' should match keyword '{$keyword}' (case-insensitive)"
            );
        }
    }

    /**
     * Property: Keyword stored in any case matches messages in any case.
     */
    #[Test]
    #[DataProvider('storedKeywordCases')]
    public function stored_keyword_case_does_not_affect_matching(
        string $storedKeyword,
        string $message,
        bool $shouldMatch
    ): void {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create(['tenant_id' => $tenant->id]);

        KeywordRule::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'keyword' => $storedKeyword,
            'reply' => 'Test reply',
            'is_active' => true,
        ]);

        $matched = $this->service->matchKeyword($device, $message);

        if ($shouldMatch) {
            $this->assertNotNull($matched);
        } else {
            $this->assertNull($matched);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PROPERTY 2: Cooldown prevents duplicate replies
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Property: Within cooldown period, canReply returns false.
     */
    #[Test]
    public function cooldown_prevents_reply_within_period(): void
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
            'reply' => 'Test reply',
            'is_active' => true,
        ]);

        // Create active cooldown (expires in 30 minutes)
        AutoReplyCooldown::create([
            'device_id' => $device->id,
            'keyword_rule_id' => $keywordRule->id,
            'from' => '6281234567890',
            'expires_at' => now()->addMinutes(30),
        ]);

        // Property: canReply returns false during cooldown
        $canReply = $this->service->canReply('test-device-123', '6281234567890', 'harga');
        $this->assertFalse($canReply);
    }

    /**
     * Property: After cooldown expires, canReply returns true.
     */
    #[Test]
    public function cooldown_allows_reply_after_expiry(): void
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
            'reply' => 'Test reply',
            'is_active' => true,
        ]);

        // Create expired cooldown
        AutoReplyCooldown::create([
            'device_id' => $device->id,
            'keyword_rule_id' => $keywordRule->id,
            'from' => '6281234567890',
            'expires_at' => now()->subMinutes(1), // Expired
        ]);

        // Property: canReply returns true after cooldown expires
        $canReply = $this->service->canReply('test-device-123', '6281234567890', 'harga');
        $this->assertTrue($canReply);
    }

    /**
     * Property: Cooldown is per sender, per keyword, per device.
     */
    #[Test]
    public function cooldown_is_scoped_correctly(): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create([
            'tenant_id' => $tenant->id,
            'gateway_device_id' => 'test-device-123',
        ]);

        $keywordRule1 = KeywordRule::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'keyword' => 'harga',
            'reply' => 'Reply 1',
            'is_active' => true,
        ]);

        $keywordRule2 = KeywordRule::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'keyword' => 'info',
            'reply' => 'Reply 2',
            'is_active' => true,
        ]);

        // Create cooldown for sender A, keyword 'harga'
        AutoReplyCooldown::create([
            'device_id' => $device->id,
            'keyword_rule_id' => $keywordRule1->id,
            'from' => '6281234567890',
            'expires_at' => now()->addMinutes(30),
        ]);

        // Property: Same sender, different keyword - can reply
        $this->assertTrue($this->service->canReply('test-device-123', '6281234567890', 'info'));

        // Property: Different sender, same keyword - can reply
        $this->assertTrue($this->service->canReply('test-device-123', '6281234567891', 'harga'));

        // Property: Same sender, same keyword - cannot reply
        $this->assertFalse($this->service->canReply('test-device-123', '6281234567890', 'harga'));
    }

    /**
     * Property: Processing message creates cooldown.
     */
    #[Test]
    public function processing_message_creates_cooldown(): void
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
            'reply' => 'Test reply',
            'is_active' => true,
        ]);

        // No cooldown initially
        $this->assertDatabaseCount('auto_reply_cooldowns', 0);

        // Process message
        $this->service->processIncomingMessage('test-device-123', '6281234567890', 'berapa harga?');

        // Property: Cooldown is created after processing
        $this->assertDatabaseHas('auto_reply_cooldowns', [
            'device_id' => $device->id,
            'keyword_rule_id' => $keywordRule->id,
            'from' => '6281234567890',
        ]);
    }

    /**
     * Property: Second message within cooldown does not dispatch job.
     */
    #[Test]
    public function second_message_within_cooldown_does_not_dispatch(): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create([
            'tenant_id' => $tenant->id,
            'gateway_device_id' => 'test-device-123',
        ]);

        KeywordRule::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'keyword' => 'harga',
            'reply' => 'Test reply',
            'is_active' => true,
        ]);

        // First message - should dispatch
        $this->service->processIncomingMessage('test-device-123', '6281234567890', 'harga');
        Queue::assertPushed(SendMessageJob::class, 1);

        // Second message within cooldown - should not dispatch
        $this->service->processIncomingMessage('test-device-123', '6281234567890', 'harga lagi');
        Queue::assertPushed(SendMessageJob::class, 1); // Still only 1

        // Property: Auto reply log shows matched but not sent
        $this->assertDatabaseHas('auto_reply_logs', [
            'device_id' => $device->id,
            'from' => '6281234567890',
            'message' => 'harga lagi',
            'matched' => true,
            'reply_sent' => false,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PROPERTY 3: Priority selection is deterministic
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Property: Given multiple matching keywords, highest priority always wins.
     */
    #[Test]
    #[DataProvider('priorityScenarios')]
    public function highest_priority_keyword_always_selected(array $keywords, string $message, int $expectedPriority): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create(['tenant_id' => $tenant->id]);

        // Create keyword rules with different priorities
        foreach ($keywords as $keyword => $priority) {
            KeywordRule::factory()->create([
                'tenant_id' => $tenant->id,
                'device_id' => $device->id,
                'keyword' => $keyword,
                'reply' => "Reply for {$keyword}",
                'is_active' => true,
                'priority' => $priority,
            ]);
        }

        $matched = $this->service->matchKeyword($device, $message);

        // Property: Matched rule has the expected (highest) priority
        $this->assertNotNull($matched);
        $this->assertEquals($expectedPriority, $matched->priority);
    }

    /**
     * Property: Priority selection is deterministic (same input = same output).
     */
    #[Test]
    public function priority_selection_is_deterministic(): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create(['tenant_id' => $tenant->id]);

        // Create multiple matching keywords
        KeywordRule::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'keyword' => 'info',
            'reply' => 'Info reply',
            'is_active' => true,
            'priority' => 5,
        ]);

        KeywordRule::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'keyword' => 'harga',
            'reply' => 'Harga reply',
            'is_active' => true,
            'priority' => 10,
        ]);

        $message = 'berapa harga dan info produk?';

        // Run multiple times
        $results = [];
        for ($i = 0; $i < 10; $i++) {
            $matched = $this->service->matchKeyword($device, $message);
            $results[] = $matched->id;
        }

        // Property: All results are identical (deterministic)
        $this->assertCount(1, array_unique($results));
    }

    /**
     * Property: Equal priority keywords have consistent selection.
     */
    #[Test]
    public function equal_priority_has_consistent_selection(): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create(['tenant_id' => $tenant->id]);

        // Create keywords with same priority
        $rule1 = KeywordRule::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'keyword' => 'info',
            'reply' => 'Info reply',
            'is_active' => true,
            'priority' => 5,
        ]);

        $rule2 = KeywordRule::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'keyword' => 'harga',
            'reply' => 'Harga reply',
            'is_active' => true,
            'priority' => 5,
        ]);

        $message = 'info dan harga';

        // Run multiple times
        $results = [];
        for ($i = 0; $i < 10; $i++) {
            $matched = $this->service->matchKeyword($device, $message);
            $results[] = $matched->id;
        }

        // Property: Results are consistent (same rule selected each time)
        $this->assertCount(1, array_unique($results));
    }

    /**
     * Property: Inactive rules are never selected regardless of priority.
     */
    #[Test]
    public function inactive_rules_never_selected(): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create(['tenant_id' => $tenant->id]);

        // High priority but inactive (different keyword to avoid unique constraint)
        KeywordRule::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'keyword' => 'promo',
            'reply' => 'Inactive reply',
            'is_active' => false,
            'priority' => 100,
        ]);

        // Low priority but active
        $activeRule = KeywordRule::factory()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'keyword' => 'harga',
            'reply' => 'Active reply',
            'is_active' => true,
            'priority' => 1,
        ]);

        // Message contains both keywords - inactive 'promo' should be ignored
        $matched = $this->service->matchKeyword($device, 'berapa harga promo?');

        // Property: Active rule is selected, not the inactive high-priority one
        $this->assertNotNull($matched);
        $this->assertEquals($activeRule->id, $matched->id);
        $this->assertTrue($matched->is_active);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PROPERTY: Auto-reply logging
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Property: Every incoming message is logged.
     */
    #[Test]
    #[DataProvider('incomingMessages')]
    public function every_incoming_message_is_logged(string $message, bool $hasMatchingKeyword): void
    {
        $tenant = Tenant::factory()->create();
        $device = Device::factory()->create([
            'tenant_id' => $tenant->id,
            'gateway_device_id' => 'test-device-123',
        ]);

        if ($hasMatchingKeyword) {
            KeywordRule::factory()->create([
                'tenant_id' => $tenant->id,
                'device_id' => $device->id,
                'keyword' => 'harga',
                'reply' => 'Test reply',
                'is_active' => true,
            ]);
        }

        $this->service->processIncomingMessage('test-device-123', '6281234567890', $message);

        // Property: Message is always logged
        $this->assertDatabaseHas('auto_reply_logs', [
            'device_id' => $device->id,
            'from' => '6281234567890',
            'message' => $message,
            'matched' => $hasMatchingKeyword,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // DATA PROVIDERS
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Generate case variations for keyword matching.
     */
    public static function caseVariations(): array
    {
        return [
            'lowercase_keyword_lowercase_message' => ['harga', 'berapa harga?', true],
            'lowercase_keyword_uppercase_message' => ['harga', 'BERAPA HARGA?', true],
            'lowercase_keyword_mixed_message' => ['harga', 'Berapa Harga?', true],
            'uppercase_keyword_lowercase_message' => ['HARGA', 'berapa harga?', true],
            'uppercase_keyword_uppercase_message' => ['HARGA', 'BERAPA HARGA?', true],
            'mixed_keyword_mixed_message' => ['HaRgA', 'BeRaPa HaRgA?', true],
            'no_match' => ['harga', 'berapa biaya?', false],
        ];
    }

    /**
     * Generate keyword case permutations.
     */
    public static function keywordCasePermutations(): array
    {
        return [
            'harga' => [
                'harga',
                ['harga', 'HARGA', 'Harga', 'HaRgA', 'hARGA'],
            ],
            'info' => [
                'info',
                ['info', 'INFO', 'Info', 'InFo', 'iNFO'],
            ],
            'promo' => [
                'promo',
                ['promo', 'PROMO', 'Promo', 'PrOmO'],
            ],
        ];
    }

    /**
     * Generate stored keyword case scenarios.
     */
    public static function storedKeywordCases(): array
    {
        return [
            'stored_lower_message_upper' => ['harga', 'HARGA BERAPA?', true],
            'stored_upper_message_lower' => ['HARGA', 'harga berapa?', true],
            'stored_mixed_message_lower' => ['HaRgA', 'harga berapa?', true],
            'no_match_different_word' => ['harga', 'biaya berapa?', false],
        ];
    }

    /**
     * Generate priority scenarios.
     */
    public static function priorityScenarios(): array
    {
        return [
            'single_match' => [
                ['harga' => 10],
                'berapa harga?',
                10,
            ],
            'two_matches_different_priority' => [
                ['harga' => 10, 'info' => 5],
                'harga dan info',
                10,
            ],
            'three_matches' => [
                ['harga' => 5, 'info' => 10, 'promo' => 3],
                'harga info promo',
                10,
            ],
            'high_priority_wins' => [
                ['a' => 1, 'b' => 100, 'c' => 50],
                'a b c',
                100,
            ],
        ];
    }

    /**
     * Generate incoming messages.
     */
    public static function incomingMessages(): array
    {
        return [
            'matching_message' => ['berapa harga?', true],
            'non_matching_message' => ['hello world', false],
            'empty_like_message' => ['...', false],
        ];
    }
}
