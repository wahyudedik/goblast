<?php

namespace Tests\Feature;

use App\Jobs\ProcessWebhookJob;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature tests for WebhookController WAHA endpoint.
 *
 * Feature: waha-migration
 * Properties covered: 10, 11, 12, 13
 */
class WahaWebhookControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('wa-automation.waha.webhook_token', 'test-token');
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PROPERTY 12: Validasi token webhook menolak semua token yang tidak cocok
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Feature: waha-migration, Property 12: Validasi token webhook menolak semua token yang tidak cocok
     *
     * For any X-Webhook-Token value that does not match the configured token,
     * the endpoint must return HTTP 401. Only the exact correct token yields HTTP 200.
     *
     * **Validates: Requirements 4.2, 4.3**
     */
    #[Test]
    #[DataProvider('wrongTokens')]
    public function wrong_token_returns_401(string $wrongToken): void
    {
        Queue::fake();

        $payload = [
            'event' => 'message',
            'session' => 'device-uuid-123',
            'payload' => ['from' => '628111@c.us', 'body' => 'hello'],
        ];

        $response = $this->postJson('/webhook/waha', $payload, [
            'X-Webhook-Token' => $wrongToken,
        ]);

        $response->assertStatus(401);
        $response->assertJson(['error' => 'Unauthorized']);
        Queue::assertNothingPushed();
    }

    #[Test]
    public function correct_token_does_not_return_401(): void
    {
        Queue::fake();

        $payload = [
            'event' => 'message',
            'session' => 'device-uuid-123',
            'payload' => ['from' => '628111@c.us', 'body' => 'hello'],
        ];

        $response = $this->postJson('/webhook/waha', $payload, [
            'X-Webhook-Token' => 'test-token',
        ]);

        $response->assertStatus(200);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PROPERTY 13: Validasi payload webhook menolak payload tanpa field wajib
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Feature: waha-migration, Property 13: Validasi payload webhook menolak payload tanpa field wajib
     *
     * For any payload missing 'event', 'session', or both, the endpoint must return HTTP 400.
     *
     * **Validates: Requirements 4.4**
     */
    #[Test]
    #[DataProvider('malformedPayloads')]
    public function malformed_payload_returns_400(array $payload): void
    {
        Queue::fake();

        $response = $this->postJson('/webhook/waha', $payload, [
            'X-Webhook-Token' => 'test-token',
        ]);

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Malformed payload']);
        Queue::assertNothingPushed();
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PROPERTY 10: Payload webhook WAHA dinormalisasi dengan benar
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Feature: waha-migration, Property 10: Payload webhook WAHA dinormalisasi dengan benar
     *
     * For any message event payload, the dispatched job must receive:
     * device_id = session, from = payload.from without @c.us, message = payload.body.
     *
     * **Validates: Requirements 5.1, 5.2**
     */
    #[Test]
    #[DataProvider('messagePayloadNormalizationCases')]
    public function message_event_payload_is_normalized_correctly(
        string $session,
        string $from,
        string $body,
        string $expectedFrom
    ): void {
        Queue::fake();

        $payload = [
            'event' => 'message',
            'session' => $session,
            'payload' => ['from' => $from, 'body' => $body],
        ];

        $this->postJson('/webhook/waha', $payload, [
            'X-Webhook-Token' => 'test-token',
        ])->assertStatus(200);

        Queue::assertPushed(ProcessWebhookJob::class, function (ProcessWebhookJob $job) use ($session, $expectedFrom, $body) {
            return $job->payload['event'] === 'message'
                && $job->payload['device_id'] === $session
                && $job->payload['from'] === $expectedFrom
                && $job->payload['message'] === $body;
        });
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PROPERTY 11: Mapping status session.status ke event internal
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Feature: waha-migration, Property 11: Mapping status session.status ke event internal
     *
     * For any session.status payload, the dispatched job must receive the correct
     * mapped internal event: WORKING → session.restore_complete,
     * FAILED → device.manual_intervention, other → session.status.
     *
     * **Validates: Requirements 5.3**
     */
    #[Test]
    #[DataProvider('sessionStatusMappingCases')]
    public function session_status_event_is_mapped_to_correct_internal_event(
        string $wahaStatus,
        string $expectedInternalEvent
    ): void {
        Queue::fake();

        $payload = [
            'event' => 'session.status',
            'session' => 'device-uuid-123',
            'payload' => ['status' => $wahaStatus],
        ];

        $this->postJson('/webhook/waha', $payload, [
            'X-Webhook-Token' => 'test-token',
        ])->assertStatus(200);

        Queue::assertPushed(ProcessWebhookJob::class, function (ProcessWebhookJob $job) use ($expectedInternalEvent) {
            return $job->payload['event'] === $expectedInternalEvent;
        });
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // EXAMPLE TESTS (Task 8.5)
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Valid webhook with correct token returns HTTP 200 and dispatches job.
     *
     * _Requirements: 4.6, 4.7_
     */
    #[Test]
    public function valid_webhook_returns_200_and_dispatches_job(): void
    {
        Queue::fake();

        $payload = [
            'event' => 'message',
            'session' => 'device-uuid-abc',
            'payload' => ['from' => '628123456789@c.us', 'body' => 'Hello world'],
        ];

        $response = $this->postJson('/webhook/waha', $payload, [
            'X-Webhook-Token' => 'test-token',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'message' => 'Webhook processed']);

        Queue::assertPushed(ProcessWebhookJob::class);
    }

    /**
     * session.status FAILED event includes 'message' field in normalized payload.
     *
     * _Requirements: 5.4_
     */
    #[Test]
    public function session_status_failed_includes_message_field_in_dispatched_payload(): void
    {
        Queue::fake();

        $payload = [
            'event' => 'session.status',
            'session' => 'device-uuid-abc',
            'payload' => ['status' => 'FAILED'],
        ];

        $this->postJson('/webhook/waha', $payload, [
            'X-Webhook-Token' => 'test-token',
        ])->assertStatus(200);

        Queue::assertPushed(ProcessWebhookJob::class, function (ProcessWebhookJob $job) {
            return $job->payload['event'] === 'device.manual_intervention'
                && isset($job->payload['message'])
                && $job->payload['message'] === 'Device requires manual intervention';
        });
    }

    /**
     * Unknown event is dispatched with the original event name.
     *
     * _Requirements: 5.5_
     */
    #[Test]
    public function unknown_event_dispatches_job_with_original_event(): void
    {
        Queue::fake();

        $payload = [
            'event' => 'some.unknown.event',
            'session' => 'device-uuid-abc',
        ];

        $this->postJson('/webhook/waha', $payload, [
            'X-Webhook-Token' => 'test-token',
        ])->assertStatus(200);

        Queue::assertPushed(ProcessWebhookJob::class, function (ProcessWebhookJob $job) {
            return $job->payload['event'] === 'some.unknown.event'
                && $job->payload['device_id'] === 'device-uuid-abc';
        });
    }

    /**
     * The /webhook/baileys endpoint still works (backward compatibility).
     *
     * _Requirements: 4.9_
     */
    #[Test]
    public function baileys_endpoint_still_works(): void
    {
        // Route exists — even without a valid signature it returns 401, not 404
        $response = $this->postJson('/webhook/baileys', []);

        $response->assertStatus(401);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // DATA PROVIDERS
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Wrong token values that should all yield HTTP 401.
     */
    public static function wrongTokens(): array
    {
        return [
            'empty_string' => [''],
            'random_string' => ['wrong-token'],
            'almost_correct' => ['test-token-extra'],
            'prefix_only' => ['test'],
            'uppercase' => ['TEST-TOKEN'],
            'with_space' => ['test-token '],
            'sql_injection_attempt' => ["' OR '1'='1"],
            'numeric_string' => ['12345'],
        ];
    }

    /**
     * Payloads missing required fields that should yield HTTP 400.
     */
    public static function malformedPayloads(): array
    {
        return [
            'missing_event' => [['session' => 'device-uuid']],
            'missing_session' => [['event' => 'message']],
            'missing_both' => [[]],
            'empty_event' => [['event' => '', 'session' => 'device-uuid']],
            'empty_session' => [['event' => 'message', 'session' => '']],
            'null_event' => [['event' => null, 'session' => 'device-uuid']],
            'null_session' => [['event' => 'message', 'session' => null]],
        ];
    }

    /**
     * Message event normalization: session, from (with/without @c.us), body → expected from.
     */
    public static function messagePayloadNormalizationCases(): array
    {
        return [
            'from_with_at_c_us' => ['session-abc', '628111222333@c.us', 'Hello', '628111222333'],
            'from_without_at_c_us' => ['session-abc', '628111222333', 'Hello', '628111222333'],
            'uuid_session' => ['550e8400-e29b-41d4-a716-446655440000', '628999888777@c.us', 'Test', '628999888777'],
            'empty_body' => ['session-1', '628111@c.us', '', '628111'],
            'unicode_body' => ['session-1', '628111@c.us', 'Halo 👋', '628111'],
            'short_number' => ['session-1', '6281@c.us', 'Hi', '6281'],
        ];
    }

    /**
     * session.status WAHA status → expected internal event mapping.
     */
    public static function sessionStatusMappingCases(): array
    {
        return [
            'working_maps_to_restore_complete' => ['WORKING', 'session.restore_complete'],
            'failed_maps_to_manual_intervention' => ['FAILED', 'device.manual_intervention'],
            'stopped_keeps_original' => ['STOPPED', 'session.status'],
            'scan_qr_code_keeps_original' => ['SCAN_QR_CODE', 'session.status'],
            'starting_keeps_original' => ['STARTING', 'session.status'],
            'unknown_keeps_original' => ['UNKNOWN_STATUS', 'session.status'],
        ];
    }
}
