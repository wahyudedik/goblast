<?php

namespace Tests\Unit\PropertyBased;

use App\Exceptions\GatewayException;
use App\Services\WahaGatewayClient;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-based tests for WahaGatewayClient correctness properties.
 *
 * Feature: waha-migration
 * Properties covered: 2, 3, 4, 5, 6, 7, 8, 9, 14
 */
class WahaGatewayClientPropertyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('wa-automation.waha.base_url', 'https://wa.konektivitas.com');
        config()->set('wa-automation.waha.api_key', 'test-api-key');
        config()->set('wa-automation.waha.webhook_url', 'https://app.test/webhook/waha');

        Http::preventStrayRequests();
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PROPERTY 14: Konversi nomor telepon ke chatId
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Property: For any phone number (with/without '+', with/without '@c.us'),
     * the chatId sent in the request body is always '{number_without_plus}@c.us'
     * without duplication.
     *
     * **Validates: Requirements 9.1, 9.2, 9.3**
     */
    #[Test]
    #[DataProvider('phoneNumberConversions')]
    public function phone_number_is_converted_to_chat_id_correctly(
        string $inputNumber,
        string $expectedChatId
    ): void {
        Http::fake([
            'https://wa.konektivitas.com/api/sendText' => Http::response(['success' => true], 200),
        ]);

        $client = new WahaGatewayClient;
        $client->sendMessage('session-1', $inputNumber, 'hello');

        Http::assertSent(function ($request) use ($expectedChatId) {
            return $request->url() === 'https://wa.konektivitas.com/api/sendText'
                && $request->data()['chatId'] === $expectedChatId;
        });
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PROPERTY 2: Header X-Api-Key selalu disertakan
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Property: For any api_key value, every HTTP request sent by sendMessage
     * includes the X-Api-Key header with the configured value.
     *
     * **Validates: Requirements 2.3**
     */
    #[Test]
    #[DataProvider('apiKeyValues')]
    public function send_message_always_includes_x_api_key_header(string $apiKey): void
    {
        config()->set('wa-automation.waha.api_key', $apiKey);

        Http::fake([
            'https://wa.konektivitas.com/api/sendText' => Http::response([], 200),
        ]);

        $client = new WahaGatewayClient;
        $client->sendMessage('session-1', '628111222333', 'test');

        Http::assertSent(fn ($request) => $request->hasHeader('X-Api-Key', $apiKey));
    }

    /**
     * Property: getConnectionStatus always includes X-Api-Key header.
     *
     * **Validates: Requirements 2.3**
     */
    #[Test]
    #[DataProvider('apiKeyValues')]
    public function get_connection_status_always_includes_x_api_key_header(string $apiKey): void
    {
        config()->set('wa-automation.waha.api_key', $apiKey);

        Http::fake([
            'https://wa.konektivitas.com/api/sessions/*' => Http::response(['status' => 'WORKING'], 200),
        ]);

        $client = new WahaGatewayClient;
        $client->getConnectionStatus('session-1');

        Http::assertSent(fn ($request) => $request->hasHeader('X-Api-Key', $apiKey));
    }

    /**
     * Property: disconnectDevice always includes X-Api-Key header on all requests.
     *
     * **Validates: Requirements 2.3**
     */
    #[Test]
    #[DataProvider('apiKeyValues')]
    public function disconnect_device_always_includes_x_api_key_header(string $apiKey): void
    {
        config()->set('wa-automation.waha.api_key', $apiKey);

        Http::fake([
            'https://wa.konektivitas.com/api/sessions/*/stop' => Http::response([], 200),
            'https://wa.konektivitas.com/api/sessions/*' => Http::response([], 200),
        ]);

        $client = new WahaGatewayClient;
        $client->disconnectDevice('session-1');

        Http::assertSent(fn ($request) => $request->hasHeader('X-Api-Key', $apiKey));
    }

    /**
     * Property: restartInstance always includes X-Api-Key header.
     *
     * **Validates: Requirements 2.3**
     */
    #[Test]
    #[DataProvider('apiKeyValues')]
    public function restart_instance_always_includes_x_api_key_header(string $apiKey): void
    {
        config()->set('wa-automation.waha.api_key', $apiKey);

        Http::fake([
            'https://wa.konektivitas.com/api/sessions/*/restart' => Http::response([], 200),
        ]);

        $client = new WahaGatewayClient;
        $client->restartInstance('session-1');

        Http::assertSent(fn ($request) => $request->hasHeader('X-Api-Key', $apiKey));
    }

    /**
     * Property: getQrCode includes X-Api-Key header on all 4 requests
     * (POST /api/sessions, POST /api/sessions/{name}/start,
     *  GET /api/sessions/{name}, GET /api/{name}/auth/qr).
     *
     * **Validates: Requirements 2.3**
     */
    #[Test]
    #[DataProvider('apiKeyValues')]
    public function get_qr_code_always_includes_x_api_key_header_on_all_requests(string $apiKey): void
    {
        config()->set('wa-automation.waha.api_key', $apiKey);

        Http::fake([
            'https://wa.konektivitas.com/api/sessions' => Http::response([], 201),
            'https://wa.konektivitas.com/api/sessions/session-1/start' => Http::response([], 200),
            'https://wa.konektivitas.com/api/sessions/session-1' => Http::response(['status' => 'SCAN_QR_CODE'], 200),
            'https://wa.konektivitas.com/api/session-1/auth/qr*' => Http::response(['value' => 'base64qr=='], 200),
        ]);

        $client = new WahaGatewayClient;
        $client->getQrCode('session-1');

        Http::assertSentCount(4);
        Http::assertSent(fn ($request) => $request->hasHeader('X-Api-Key', $apiKey));
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PROPERTY 3: sendMessage memformat request dengan benar
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Property: For any sessionName, to, and message, sendMessage sends a POST
     * to /api/sendText with body containing session=sessionName, chatId={to}@c.us,
     * text=message.
     *
     * **Validates: Requirements 2.4, 9.1, 9.2, 9.3**
     */
    #[Test]
    #[DataProvider('sendMessageInputCombinations')]
    public function send_message_formats_request_body_correctly(
        string $sessionName,
        string $to,
        string $message,
        string $expectedChatId
    ): void {
        Http::fake([
            'https://wa.konektivitas.com/api/sendText' => Http::response([], 200),
        ]);

        $client = new WahaGatewayClient;
        $client->sendMessage($sessionName, $to, $message);

        Http::assertSent(function ($request) use ($sessionName, $expectedChatId, $message) {
            $data = $request->data();

            return $request->url() === 'https://wa.konektivitas.com/api/sendText'
                && $request->method() === 'POST'
                && $data['session'] === $sessionName
                && $data['chatId'] === $expectedChatId
                && $data['text'] === $message;
        });
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PROPERTY 4: sendMessage sukses mengembalikan GatewayResponse yang benar
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Property: For any HTTP 2xx response code, sendMessage returns a GatewayResponse
     * with success=true and status='sent'.
     *
     * **Validates: Requirements 2.5**
     */
    #[Test]
    #[DataProvider('httpSuccessCodes')]
    public function send_message_success_returns_correct_gateway_response(int $statusCode): void
    {
        Http::fake([
            'https://wa.konektivitas.com/api/sendText' => Http::response([], $statusCode),
        ]);

        $client = new WahaGatewayClient;
        $response = $client->sendMessage('session-1', '628111222333', 'hello');

        $this->assertTrue($response->success);
        $this->assertSame('sent', $response->status);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PROPERTY 5: sendMessage gagal melempar GatewayException
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Property: For any HTTP 4xx or 5xx response code, sendMessage throws GatewayException.
     *
     * **Validates: Requirements 2.6**
     */
    #[Test]
    #[DataProvider('httpErrorCodes')]
    public function send_message_failure_throws_gateway_exception(int $statusCode): void
    {
        Http::fake([
            'https://wa.konektivitas.com/api/sendText' => Http::response(['error' => 'failed'], $statusCode),
        ]);

        $this->expectException(GatewayException::class);

        $client = new WahaGatewayClient;
        $client->sendMessage('session-1', '628111222333', 'hello');
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PROPERTY 6: getQrCode memformat URL request dengan benar
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Property: For any sessionName, getQrCode sends a GET request to a URL
     * containing /{sessionName}/auth/qr with query parameter format=base64.
     *
     * **Validates: Requirements 2.7**
     */
    #[Test]
    #[DataProvider('sessionNames')]
    public function get_qr_code_formats_url_correctly(string $sessionName): void
    {
        Http::fake([
            'https://wa.konektivitas.com/api/sessions' => Http::response([], 201),
            "https://wa.konektivitas.com/api/sessions/{$sessionName}/start" => Http::response([], 200),
            "https://wa.konektivitas.com/api/sessions/{$sessionName}" => Http::response(['status' => 'SCAN_QR_CODE'], 200),
            "https://wa.konektivitas.com/api/{$sessionName}/auth/qr*" => Http::response(['value' => 'base64data=='], 200),
        ]);

        $client = new WahaGatewayClient;
        $client->getQrCode($sessionName);

        Http::assertSent(function ($request) use ($sessionName) {
            return str_contains($request->url(), "/{$sessionName}/auth/qr")
                && $request->method() === 'GET'
                && str_contains($request->url(), 'format=base64');
        });
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PROPERTY 7: getQrCode mengembalikan nilai dari field value
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Property: For any base64 string returned by WAHA in the 'value' field,
     * getQrCode returns that exact string.
     *
     * **Validates: Requirements 2.8**
     */
    #[Test]
    #[DataProvider('base64QrValues')]
    public function get_qr_code_returns_value_field_from_response(string $base64Value): void
    {
        Http::fake([
            'https://wa.konektivitas.com/api/sessions' => Http::response([], 201),
            'https://wa.konektivitas.com/api/sessions/session-1/start' => Http::response([], 200),
            'https://wa.konektivitas.com/api/sessions/session-1' => Http::response(['status' => 'SCAN_QR_CODE'], 200),
            'https://wa.konektivitas.com/api/session-1/auth/qr*' => Http::response(['value' => $base64Value], 200),
        ]);

        $client = new WahaGatewayClient;
        $result = $client->getQrCode('session-1');

        $this->assertSame($base64Value, $result);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PROPERTY 8: getConnectionStatus memetakan status non-WORKING ke 'disconnected'
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Property: For any status string that is not 'WORKING', getConnectionStatus
     * returns 'disconnected'.
     *
     * **Validates: Requirements 2.12**
     */
    #[Test]
    #[DataProvider('nonWorkingStatuses')]
    public function get_connection_status_maps_non_working_to_disconnected(string $status): void
    {
        Http::fake([
            'https://wa.konektivitas.com/api/sessions/session-1' => Http::response(['status' => $status], 200),
        ]);

        $client = new WahaGatewayClient;
        $result = $client->getConnectionStatus('session-1');

        $this->assertSame('disconnected', $result);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PROPERTY 9: disconnectDevice dan restartInstance memformat URL dengan benar
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Property: For any sessionName, disconnectDevice sends POST to
     * /api/sessions/{sessionName}/stop AND DELETE to /api/sessions/{sessionName}.
     *
     * **Validates: Requirements 2.14, 3.7**
     */
    #[Test]
    #[DataProvider('sessionNames')]
    public function disconnect_device_sends_stop_then_delete(string $sessionName): void
    {
        Http::fake([
            "https://wa.konektivitas.com/api/sessions/{$sessionName}/stop" => Http::response([], 200),
            "https://wa.konektivitas.com/api/sessions/{$sessionName}" => Http::response([], 200),
        ]);

        $client = new WahaGatewayClient;
        $client->disconnectDevice($sessionName);

        Http::assertSent(function ($request) use ($sessionName) {
            return $request->url() === "https://wa.konektivitas.com/api/sessions/{$sessionName}/stop"
                && $request->method() === 'POST';
        });

        Http::assertSent(function ($request) use ($sessionName) {
            return $request->url() === "https://wa.konektivitas.com/api/sessions/{$sessionName}"
                && $request->method() === 'DELETE';
        });
    }

    /**
     * Property: For any sessionName, restartInstance sends POST to
     * /api/sessions/{sessionName}/restart.
     *
     * **Validates: Requirements 2.16**
     */
    #[Test]
    #[DataProvider('sessionNames')]
    public function restart_instance_sends_post_to_restart_url(string $sessionName): void
    {
        Http::fake([
            "https://wa.konektivitas.com/api/sessions/{$sessionName}/restart" => Http::response([], 200),
        ]);

        $client = new WahaGatewayClient;
        $client->restartInstance($sessionName);

        Http::assertSent(function ($request) use ($sessionName) {
            return $request->url() === "https://wa.konektivitas.com/api/sessions/{$sessionName}/restart"
                && $request->method() === 'POST';
        });
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // DATA PROVIDERS
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Phone number input → expected chatId output combinations.
     */
    public static function phoneNumberConversions(): array
    {
        return [
            'plain_number' => ['628123456789', '628123456789@c.us'],
            'with_plus_prefix' => ['+628123456789', '628123456789@c.us'],
            'already_has_at_c_us' => ['628123456789@c.us', '628123456789@c.us'],
            'plus_and_at_c_us' => ['+628123456789@c.us', '628123456789@c.us'],
            'short_number' => ['6281', '6281@c.us'],
            'plus_short_number' => ['+6281', '6281@c.us'],
            'international_format' => ['6285678901234', '6285678901234@c.us'],
            'plus_international' => ['+6285678901234', '6285678901234@c.us'],
        ];
    }

    /**
     * Various api_key values to test header inclusion.
     */
    public static function apiKeyValues(): array
    {
        return [
            'simple_key' => ['test-api-key'],
            'long_key' => ['sk-very-long-api-key-with-many-characters-1234567890'],
            'alphanumeric' => ['abc123XYZ'],
            'with_special_chars' => ['key_with-dashes.and_underscores'],
            'uuid_style' => ['550e8400-e29b-41d4-a716-446655440000'],
        ];
    }

    /**
     * Various sessionName, to, message combinations for sendMessage format test.
     */
    public static function sendMessageInputCombinations(): array
    {
        return [
            'basic' => ['session-abc', '628111222333', 'Hello!', '628111222333@c.us'],
            'with_plus' => ['session-abc', '+628111222333', 'Hello!', '628111222333@c.us'],
            'already_chatid' => ['session-abc', '628111222333@c.us', 'Hello!', '628111222333@c.us'],
            'uuid_session' => ['550e8400-e29b-41d4-a716-446655440000', '628999888777', 'Test msg', '628999888777@c.us'],
            'multiline_message' => ['session-1', '628111222333', "Line 1\nLine 2", '628111222333@c.us'],
            'unicode_message' => ['session-1', '628111222333', 'Halo 👋 dunia', '628111222333@c.us'],
            'empty_message' => ['session-1', '628111222333', '', '628111222333@c.us'],
        ];
    }

    /**
     * HTTP 2xx status codes.
     */
    public static function httpSuccessCodes(): array
    {
        return [
            'http_200' => [200],
            'http_201' => [201],
            'http_202' => [202],
            'http_204' => [204],
        ];
    }

    /**
     * HTTP 4xx and 5xx error codes.
     */
    public static function httpErrorCodes(): array
    {
        return [
            'http_400' => [400],
            'http_401' => [401],
            'http_403' => [403],
            'http_404' => [404],
            'http_422' => [422],
            'http_429' => [429],
            'http_500' => [500],
            'http_502' => [502],
            'http_503' => [503],
        ];
    }

    /**
     * Various session names.
     */
    public static function sessionNames(): array
    {
        return [
            'simple' => ['session-1'],
            'uuid' => ['550e8400-e29b-41d4-a716-446655440000'],
            'alphanumeric' => ['mySession123'],
            'with_underscores' => ['my_session_name'],
            'short' => ['s1'],
        ];
    }

    /**
     * Various base64 QR code values.
     */
    public static function base64QrValues(): array
    {
        return [
            'short_base64' => ['abc123=='],
            'typical_qr' => ['iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='],
            'data_uri_style' => ['data:image/png;base64,iVBORw0KGgo='],
            'long_value' => [str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/', 10).'=='],
        ];
    }

    /**
     * Non-WORKING WAHA session statuses.
     */
    public static function nonWorkingStatuses(): array
    {
        return [
            'stopped' => ['STOPPED'],
            'failed' => ['FAILED'],
            'scan_qr_code' => ['SCAN_QR_CODE'],
            'starting' => ['STARTING'],
            'unknown' => ['UNKNOWN'],
            'empty_string' => [''],
            'lowercase_working' => ['working'],
            'mixed_case' => ['Working'],
            'random_string' => ['SOME_OTHER_STATUS'],
            'disconnected_literal' => ['DISCONNECTED'],
        ];
    }
}
