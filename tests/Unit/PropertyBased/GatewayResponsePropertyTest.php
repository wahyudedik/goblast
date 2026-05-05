<?php

namespace Tests\Unit\PropertyBased;

use App\Services\ValueObjects\GatewayResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-based tests for GatewayResponse correctness properties.
 *
 * Feature: waha-migration, Property 1: GatewayResponse menyimpan nilai dengan benar
 *
 * Validates: Requirements 1.6
 */
class GatewayResponsePropertyTest extends TestCase
{
    // ═══════════════════════════════════════════════════════════════════════════
    // PROPERTY 1: GatewayResponse menyimpan nilai dengan benar
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Property: For any combination of success, status, messageId, and errorMessage,
     * reading back the properties must return values identical to those given at construction.
     *
     * **Validates: Requirements 1.6**
     */
    #[Test]
    #[DataProvider('gatewayResponseCombinations')]
    public function properties_round_trip_correctly(
        bool $success,
        string $status,
        ?string $messageId,
        ?string $errorMessage
    ): void {
        $response = new GatewayResponse(
            success: $success,
            status: $status,
            messageId: $messageId,
            errorMessage: $errorMessage,
        );

        $this->assertSame($success, $response->success);
        $this->assertSame($status, $response->status);
        $this->assertSame($messageId, $response->messageId);
        $this->assertSame($errorMessage, $response->errorMessage);
    }

    /**
     * Property: Default values for optional parameters are null.
     *
     * **Validates: Requirements 1.6**
     */
    #[Test]
    #[DataProvider('successAndStatusCombinations')]
    public function optional_properties_default_to_null(bool $success, string $status): void
    {
        $response = new GatewayResponse(success: $success, status: $status);

        $this->assertNull($response->messageId);
        $this->assertNull($response->errorMessage);
    }

    /**
     * Property: success=true with status='sent' represents a successful send.
     *
     * **Validates: Requirements 1.6**
     */
    #[Test]
    #[DataProvider('successfulResponseCombinations')]
    public function successful_response_preserves_all_fields(
        string $status,
        ?string $messageId
    ): void {
        $response = new GatewayResponse(
            success: true,
            status: $status,
            messageId: $messageId,
        );

        $this->assertTrue($response->success);
        $this->assertSame($status, $response->status);
        $this->assertSame($messageId, $response->messageId);
        $this->assertNull($response->errorMessage);
    }

    /**
     * Property: success=false with errorMessage represents a failed operation.
     *
     * **Validates: Requirements 1.6**
     */
    #[Test]
    #[DataProvider('failedResponseCombinations')]
    public function failed_response_preserves_all_fields(
        string $status,
        string $errorMessage
    ): void {
        $response = new GatewayResponse(
            success: false,
            status: $status,
            errorMessage: $errorMessage,
        );

        $this->assertFalse($response->success);
        $this->assertSame($status, $response->status);
        $this->assertNull($response->messageId);
        $this->assertSame($errorMessage, $response->errorMessage);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // DATA PROVIDERS
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Full combination matrix: success × status × messageId × errorMessage.
     */
    public static function gatewayResponseCombinations(): array
    {
        $successValues = [true, false];
        $statusValues = ['sent', 'failed', 'error', 'pending', 'queued', ''];
        $messageIdValues = [null, 'msg-abc-123', 'wamid.HBgLNjI4MTIzNDU2Nzg5', ''];
        $errorMessageValues = [null, 'Connection refused', 'Timeout', 'Invalid phone number', ''];

        $cases = [];

        foreach ($successValues as $success) {
            foreach ($statusValues as $status) {
                foreach ($messageIdValues as $messageId) {
                    foreach ($errorMessageValues as $errorMessage) {
                        $key = sprintf(
                            'success=%s_status=%s_msgId=%s_errMsg=%s',
                            $success ? 'true' : 'false',
                            $status ?: 'empty',
                            $messageId ?? 'null',
                            $errorMessage ?? 'null',
                        );
                        $cases[$key] = [$success, $status, $messageId, $errorMessage];
                    }
                }
            }
        }

        return $cases;
    }

    /**
     * Combinations of success and status for default-value tests.
     */
    public static function successAndStatusCombinations(): array
    {
        return [
            'success_true_sent' => [true, 'sent'],
            'success_true_pending' => [true, 'pending'],
            'success_false_failed' => [false, 'failed'],
            'success_false_error' => [false, 'error'],
            'success_true_empty_status' => [true, ''],
            'success_false_empty_status' => [false, ''],
        ];
    }

    /**
     * Successful response combinations with various statuses and messageIds.
     */
    public static function successfulResponseCombinations(): array
    {
        return [
            'sent_with_message_id' => ['sent', 'msg-abc-123'],
            'sent_without_message_id' => ['sent', null],
            'queued_with_message_id' => ['queued', 'msg-xyz-456'],
            'queued_without_message_id' => ['queued', null],
            'pending_with_wamid' => ['pending', 'wamid.HBgLNjI4MTIzNDU2Nzg5'],
        ];
    }

    /**
     * Failed response combinations with various statuses and error messages.
     */
    public static function failedResponseCombinations(): array
    {
        return [
            'failed_connection_refused' => ['failed', 'Connection refused'],
            'error_timeout' => ['error', 'Request timeout after 30 seconds'],
            'failed_invalid_phone' => ['failed', 'Invalid phone number format'],
            'error_api_key' => ['error', 'Invalid API key'],
            'failed_session_not_found' => ['failed', 'Session not found'],
        ];
    }
}
