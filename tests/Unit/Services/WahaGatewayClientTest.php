<?php

namespace Tests\Unit\Services;

use App\Exceptions\GatewayException;
use App\Services\WahaGatewayClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Example unit tests for WahaGatewayClient.
 *
 * Feature: waha-migration
 */
class WahaGatewayClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('wa-automation.waha.base_url', 'https://wa.konektivitas.com');
        config()->set('wa-automation.waha.api_key', 'test-api-key');
        config()->set('wa-automation.waha.webhook_url', 'https://app.test/webhook/waha');

        Http::preventStrayRequests();
    }

    // ─── getConnectionStatus ──────────────────────────────────────────────────

    public function test_get_connection_status_returns_connected_when_status_is_working(): void
    {
        Http::fake([
            'https://wa.konektivitas.com/api/sessions/my-session' => Http::response(
                ['status' => 'WORKING'],
                200
            ),
        ]);

        $client = new WahaGatewayClient;
        $result = $client->getConnectionStatus('my-session');

        $this->assertSame('connected', $result);
    }

    public function test_get_connection_status_returns_error_on_network_exception(): void
    {
        Http::fake([
            'https://wa.konektivitas.com/api/sessions/my-session' => function () {
                throw new ConnectionException('Connection refused');
            },
        ]);

        $client = new WahaGatewayClient;
        $result = $client->getConnectionStatus('my-session');

        $this->assertSame('error', $result);
    }

    public function test_get_connection_status_returns_disconnected_for_non_working_status(): void
    {
        Http::fake([
            'https://wa.konektivitas.com/api/sessions/my-session' => Http::response(
                ['status' => 'STOPPED'],
                200
            ),
        ]);

        $client = new WahaGatewayClient;
        $result = $client->getConnectionStatus('my-session');

        $this->assertSame('disconnected', $result);
    }

    // ─── sendMessage retries ──────────────────────────────────────────────────

    public function test_send_message_retries_on_network_error_then_throws_gateway_exception(): void
    {
        // All 3 attempts fail with a network error
        Http::fake([
            'https://wa.konektivitas.com/api/sendText' => function () {
                throw new ConnectionException('Network unreachable');
            },
        ]);

        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('Failed to send message after retries');

        $client = new WahaGatewayClient;
        $client->sendMessage('session-1', '628111222333', 'hello');
    }

    public function test_send_message_succeeds_after_network_errors(): void
    {
        $callCount = 0;

        Http::fake([
            'https://wa.konektivitas.com/api/sendText' => function () use (&$callCount) {
                $callCount++;
                if ($callCount < 3) {
                    throw new ConnectionException('Temporary network error');
                }

                return Http::response([], 200);
            },
        ]);

        $client = new WahaGatewayClient;
        $response = $client->sendMessage('session-1', '628111222333', 'hello');

        $this->assertTrue($response->success);
        $this->assertSame('sent', $response->status);
        $this->assertSame(3, $callCount);
    }

    // ─── getQrCode idempotency ────────────────────────────────────────────────

    public function test_get_qr_code_treats_422_with_exists_as_success(): void
    {
        Http::fake([
            'https://wa.konektivitas.com/api/sessions' => Http::response(
                ['error' => 'Session already exists'],
                422
            ),
            'https://wa.konektivitas.com/api/sessions/session-1/start' => Http::response([], 200),
            'https://wa.konektivitas.com/api/sessions/session-1' => Http::response(['status' => 'SCAN_QR_CODE'], 200),
            'https://wa.konektivitas.com/api/session-1/auth/qr*' => Http::response(['value' => 'qr-base64=='], 200),
        ]);

        $client = new WahaGatewayClient;
        $result = $client->getQrCode('session-1');

        $this->assertSame('qr-base64==', $result);
    }

    public function test_get_qr_code_treats_422_with_already_as_success(): void
    {
        Http::fake([
            'https://wa.konektivitas.com/api/sessions' => Http::response(
                ['message' => 'Session name already in use'],
                422
            ),
            'https://wa.konektivitas.com/api/sessions/session-1/start' => Http::response([], 200),
            'https://wa.konektivitas.com/api/sessions/session-1' => Http::response(['status' => 'SCAN_QR_CODE'], 200),
            'https://wa.konektivitas.com/api/session-1/auth/qr*' => Http::response(['value' => 'qr-base64=='], 200),
        ]);

        $client = new WahaGatewayClient;
        $result = $client->getQrCode('session-1');

        $this->assertSame('qr-base64==', $result);
    }

    public function test_get_qr_code_throws_on_422_without_exists_or_already(): void
    {
        Http::fake([
            'https://wa.konektivitas.com/api/sessions' => Http::response(
                ['error' => 'Validation failed: name is required'],
                422
            ),
        ]);

        $this->expectException(GatewayException::class);

        $client = new WahaGatewayClient;
        $client->getQrCode('session-1');
    }

    // ─── getQrCode polling ────────────────────────────────────────────────────

    public function test_get_qr_code_succeeds_after_multiple_polls(): void
    {
        Http::fake([
            'https://wa.konektivitas.com/api/sessions' => Http::response([], 201),
            'https://wa.konektivitas.com/api/sessions/session-1/start' => Http::response([], 200),
            'https://wa.konektivitas.com/api/sessions/session-1' => Http::sequence()
                ->push(['status' => 'STARTING'], 200)
                ->push(['status' => 'STARTING'], 200)
                ->push(['status' => 'SCAN_QR_CODE'], 200),
            'https://wa.konektivitas.com/api/session-1/auth/qr*' => Http::response(['value' => 'qr-data=='], 200),
        ]);

        $client = new WahaGatewayClient;
        $result = $client->getQrCode('session-1');

        $this->assertSame('qr-data==', $result);
    }

    public function test_get_qr_code_throws_when_scan_qr_code_not_reached_after_max_polls(): void
    {
        // Build a sequence with enough STARTING responses to exhaust all 12 poll attempts
        $sequence = Http::sequence();
        for ($i = 0; $i < 12; $i++) {
            $sequence->push(['status' => 'STARTING'], 200);
        }

        Http::fake([
            'https://wa.konektivitas.com/api/sessions' => Http::response([], 201),
            'https://wa.konektivitas.com/api/sessions/session-1/start' => Http::response([], 200),
            'https://wa.konektivitas.com/api/sessions/session-1' => $sequence,
        ]);

        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('Session not ready for QR code');

        $client = new WahaGatewayClient;
        $client->getQrCode('session-1');
    }

    // ─── disconnectDevice ─────────────────────────────────────────────────────

    public function test_disconnect_device_calls_stop_then_delete(): void
    {
        Http::fake([
            'https://wa.konektivitas.com/api/sessions/session-1/stop' => Http::response([], 200),
            'https://wa.konektivitas.com/api/sessions/session-1' => Http::response([], 200),
        ]);

        $client = new WahaGatewayClient;
        $client->disconnectDevice('session-1');

        $requests = Http::recorded();
        $this->assertCount(2, $requests);

        [$stopRequest] = $requests[0];
        $this->assertSame('POST', $stopRequest->method());
        $this->assertStringEndsWith('/api/sessions/session-1/stop', $stopRequest->url());

        [$deleteRequest] = $requests[1];
        $this->assertSame('DELETE', $deleteRequest->method());
        $this->assertStringEndsWith('/api/sessions/session-1', $deleteRequest->url());
    }

    public function test_disconnect_device_throws_gateway_exception_when_stop_fails(): void
    {
        Http::fake([
            'https://wa.konektivitas.com/api/sessions/session-1/stop' => Http::response([], 500),
        ]);

        $this->expectException(GatewayException::class);

        $client = new WahaGatewayClient;
        $client->disconnectDevice('session-1');
    }

    // ─── restartInstance ──────────────────────────────────────────────────────

    public function test_restart_instance_throws_gateway_exception_on_failure(): void
    {
        Http::fake([
            'https://wa.konektivitas.com/api/sessions/session-1/restart' => Http::response([], 503),
        ]);

        $this->expectException(GatewayException::class);

        $client = new WahaGatewayClient;
        $client->restartInstance('session-1');
    }
}
