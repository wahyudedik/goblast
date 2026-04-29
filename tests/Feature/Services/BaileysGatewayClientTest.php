<?php

namespace Tests\Feature\Services;

use App\Exceptions\GatewayException;
use App\Services\BaileysGatewayClient;
use App\Services\ValueObjects\BaileysResponse;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BaileysGatewayClientTest extends TestCase
{
    private BaileysGatewayClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new BaileysGatewayClient(
            baseUrl: 'http://localhost:3000',
            webhookSecret: 'test-secret-key'
        );
    }

    public function test_send_message_success(): void
    {
        Http::fake([
            'http://localhost:3000/api/send-message' => Http::response([
                'success' => true,
                'status' => 'sent',
                'message_id' => 'msg_123',
            ], 200),
        ]);

        $response = $this->client->sendMessage('device_1', '6281234567890', 'Hello World');

        $this->assertInstanceOf(BaileysResponse::class, $response);
        $this->assertTrue($response->success);
        $this->assertEquals('sent', $response->status);
        $this->assertEquals('msg_123', $response->messageId);
    }

    public function test_send_message_with_error_response(): void
    {
        Http::fake([
            'http://localhost:3000/api/send-message' => Http::response([
                'success' => false,
                'status' => 'failed',
                'error' => 'Device not connected',
            ], 200),
        ]);

        $response = $this->client->sendMessage('device_1', '6281234567890', 'Hello World');

        $this->assertFalse($response->success);
        $this->assertEquals('failed', $response->status);
        $this->assertEquals('Device not connected', $response->errorMessage);
    }

    public function test_send_message_http_failure(): void
    {
        Http::fake([
            'http://localhost:3000/api/send-message' => Http::response([], 500),
        ]);

        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('Failed to send message: HTTP 500');

        $this->client->sendMessage('device_1', '6281234567890', 'Hello World');
    }

    public function test_send_message_network_error(): void
    {
        Http::fake([
            'http://localhost:3000/api/send-message' => Http::response([], 500),
        ]);

        $this->expectException(GatewayException::class);

        $this->client->sendMessage('device_1', '6281234567890', 'Hello World');
    }

    public function test_get_qr_code_success(): void
    {
        Http::fake([
            'http://localhost:3000/api/qr-code/device_1' => Http::response([
                'qr_code' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
            ], 200),
        ]);

        $qrCode = $this->client->getQrCode('device_1');

        $this->assertStringStartsWith('data:image/png;base64,', $qrCode);
    }

    public function test_get_qr_code_missing_in_response(): void
    {
        Http::fake([
            'http://localhost:3000/api/qr-code/device_1' => Http::response([], 200),
        ]);

        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('QR code not found in response');

        $this->client->getQrCode('device_1');
    }

    public function test_get_qr_code_http_failure(): void
    {
        Http::fake([
            'http://localhost:3000/api/qr-code/device_1' => Http::response([], 404),
        ]);

        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('Failed to get QR code: HTTP 404');

        $this->client->getQrCode('device_1');
    }

    public function test_get_connection_status_connected(): void
    {
        Http::fake([
            'http://localhost:3000/api/device-status/device_1' => Http::response([
                'status' => 'connected',
            ], 200),
        ]);

        $status = $this->client->getConnectionStatus('device_1');

        $this->assertEquals('connected', $status);
    }

    public function test_get_connection_status_disconnected(): void
    {
        Http::fake([
            'http://localhost:3000/api/device-status/device_1' => Http::response([
                'status' => 'disconnected',
            ], 200),
        ]);

        $status = $this->client->getConnectionStatus('device_1');

        $this->assertEquals('disconnected', $status);
    }

    public function test_get_connection_status_http_failure_returns_error(): void
    {
        Http::fake([
            'http://localhost:3000/api/device-status/device_1' => Http::response([], 500),
        ]);

        $status = $this->client->getConnectionStatus('device_1');

        $this->assertEquals('error', $status);
    }

    public function test_get_connection_status_network_error_returns_error(): void
    {
        Http::fake([
            'http://localhost:3000/api/device-status/device_1' => Http::sequence()
                ->push(new \Exception('Connection refused')),
        ]);

        $status = $this->client->getConnectionStatus('device_1');

        $this->assertEquals('error', $status);
    }

    public function test_disconnect_device_success(): void
    {
        Http::fake([
            'http://localhost:3000/api/disconnect/device_1' => Http::response([
                'success' => true,
            ], 200),
        ]);

        $this->client->disconnectDevice('device_1');

        Http::assertSent(function ($request) {
            return $request->url() === 'http://localhost:3000/api/disconnect/device_1';
        });
    }

    public function test_disconnect_device_http_failure(): void
    {
        Http::fake([
            'http://localhost:3000/api/disconnect/device_1' => Http::response([], 500),
        ]);

        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('Failed to disconnect device: HTTP 500');

        $this->client->disconnectDevice('device_1');
    }

    public function test_disconnect_device_network_error(): void
    {
        Http::fake([
            'http://localhost:3000/api/disconnect/device_1' => Http::response([], 500),
        ]);

        $this->expectException(GatewayException::class);

        $this->client->disconnectDevice('device_1');
    }

    public function test_restart_instance_success(): void
    {
        Http::fake([
            'http://localhost:3000/api/restart-instance/instance_1' => Http::response([
                'success' => true,
            ], 200),
        ]);

        $this->client->restartInstance('instance_1');

        Http::assertSent(function ($request) {
            return $request->url() === 'http://localhost:3000/api/restart-instance/instance_1';
        });
    }

    public function test_restart_instance_http_failure(): void
    {
        Http::fake([
            'http://localhost:3000/api/restart-instance/instance_1' => Http::response([], 500),
        ]);

        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('Failed to restart instance: HTTP 500');

        $this->client->restartInstance('instance_1');
    }

    public function test_restart_instance_network_error(): void
    {
        Http::fake([
            'http://localhost:3000/api/restart-instance/instance_1' => Http::response([], 500),
        ]);

        $this->expectException(GatewayException::class);

        $this->client->restartInstance('instance_1');
    }

    public function test_validate_webhook_signature_valid(): void
    {
        $payload = '{"event":"message.received","device_id":"device_1"}';
        $secret = config('wa-automation.baileys.webhook_secret');
        $signature = hash_hmac('sha256', $payload, $secret);

        $this->assertTrue(BaileysGatewayClient::validateWebhookSignature($payload, $signature));
    }

    public function test_validate_webhook_signature_invalid(): void
    {
        $payload = '{"event":"message.received","device_id":"device_1"}';
        $invalidSignature = 'invalid_signature_hash';

        $this->assertFalse(BaileysGatewayClient::validateWebhookSignature($payload, $invalidSignature));
    }

    public function test_validate_webhook_signature_tampered_payload(): void
    {
        $payload = '{"event":"message.received","device_id":"device_1"}';
        $secret = 'test-secret-key';
        $signature = hash_hmac('sha256', $payload, $secret);

        $tamperedPayload = '{"event":"message.received","device_id":"device_2"}';

        $this->assertFalse(BaileysGatewayClient::validateWebhookSignature($tamperedPayload, $signature));
    }
}
