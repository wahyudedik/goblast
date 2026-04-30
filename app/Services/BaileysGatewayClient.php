<?php

namespace App\Services;

use App\Exceptions\GatewayException;
use App\Services\Contracts\BaileysGatewayClientInterface;
use App\Services\ValueObjects\BaileysResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BaileysGatewayClient implements BaileysGatewayClientInterface
{
    private const TIMEOUT = 30;

    private const CONNECT_TIMEOUT = 10;

    private const MAX_RETRIES = 3;

    private const RETRY_DELAY = [1, 2, 4]; // exponential backoff in seconds

    public function __construct(
        private string $baseUrl = '',
        private string $webhookSecret = '',
    ) {
        $this->baseUrl = $baseUrl ?: config('wa-automation.baileys.gateway_url');
        $this->webhookSecret = $webhookSecret ?: config('wa-automation.baileys.webhook_secret');
    }

    /**
     * Send a message through Baileys Gateway.
     *
     * @throws GatewayException
     */
    public function sendMessage(string $deviceId, string $to, string $message): BaileysResponse
    {
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->connectTimeout(self::CONNECT_TIMEOUT)
                ->retry(self::MAX_RETRIES, function (int $attempt) {
                    return $attempt * 1000; // milliseconds
                }, throw: false)
                ->post("{$this->baseUrl}/api/send-message", [
                    'device_id' => $deviceId,
                    'to' => $to,
                    'message' => $message,
                ]);

            if ($response->failed()) {
                Log::warning('Baileys Gateway send message failed', [
                    'device_id' => $deviceId,
                    'to' => $to,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new GatewayException(
                    "Failed to send message: HTTP {$response->status()}",
                    $response->body(),
                );
            }

            $data = $response->json();

            return new BaileysResponse(
                success: $data['success'] ?? false,
                status: $data['status'] ?? 'unknown',
                messageId: $data['message_id'] ?? null,
                errorMessage: $data['error'] ?? null,
            );
        } catch (GatewayException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Baileys Gateway send message exception', [
                'device_id' => $deviceId,
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            throw new GatewayException(
                'Failed to send message: ' . $e->getMessage(),
                $e->getMessage(),
            );
        }
    }

    /**
     * Request a QR code for device connection.
     *
     * @throws GatewayException
     */
    public function getQrCode(string $deviceId): string
    {
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->connectTimeout(self::CONNECT_TIMEOUT)
                ->get("{$this->baseUrl}/api/qr-code/{$deviceId}");

            if ($response->failed()) {
                Log::warning('Baileys Gateway get QR code failed', [
                    'device_id' => $deviceId,
                    'status' => $response->status(),
                ]);

                throw new GatewayException(
                    "Failed to get QR code: HTTP {$response->status()}",
                    $response->body(),
                );
            }

            $data = $response->json();

            if (! isset($data['qr_code'])) {
                throw new GatewayException('QR code not found in response');
            }

            return $data['qr_code'];
        } catch (GatewayException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Baileys Gateway get QR code exception', [
                'device_id' => $deviceId,
                'error' => $e->getMessage(),
            ]);

            throw new GatewayException(
                'Failed to get QR code: ' . $e->getMessage(),
                $e->getMessage(),
            );
        }
    }

    /**
     * Get the current connection status of a device.
     *
     * @return string One of: 'connected', 'disconnected', 'error'
     *
     * @throws GatewayException
     */
    public function getConnectionStatus(string $deviceId): string
    {
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->connectTimeout(self::CONNECT_TIMEOUT)
                ->get("{$this->baseUrl}/api/device-status/{$deviceId}");

            if ($response->failed()) {
                Log::warning('Baileys Gateway get connection status failed', [
                    'device_id' => $deviceId,
                    'status' => $response->status(),
                ]);

                return 'error';
            }

            $data = $response->json();

            return $data['status'] ?? 'error';
        } catch (\Exception $e) {
            Log::error('Baileys Gateway get connection status exception', [
                'device_id' => $deviceId,
                'error' => $e->getMessage(),
            ]);

            return 'error';
        }
    }

    /**
     * Disconnect a device from Baileys Gateway.
     *
     * @throws GatewayException
     */
    public function disconnectDevice(string $deviceId): void
    {
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->connectTimeout(self::CONNECT_TIMEOUT)
                ->post("{$this->baseUrl}/api/disconnect/{$deviceId}");

            if ($response->failed()) {
                Log::warning('Baileys Gateway disconnect device failed', [
                    'device_id' => $deviceId,
                    'status' => $response->status(),
                ]);

                throw new GatewayException(
                    "Failed to disconnect device: HTTP {$response->status()}",
                    $response->body(),
                );
            }

            Log::info('Device disconnected successfully', [
                'device_id' => $deviceId,
            ]);
        } catch (GatewayException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Baileys Gateway disconnect device exception', [
                'device_id' => $deviceId,
                'error' => $e->getMessage(),
            ]);

            throw new GatewayException(
                'Failed to disconnect device: ' . $e->getMessage(),
                $e->getMessage(),
            );
        }
    }

    /**
     * Restart a Baileys Gateway instance.
     *
     * @throws GatewayException
     */
    public function restartInstance(string $instanceId): void
    {
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->connectTimeout(self::CONNECT_TIMEOUT)
                ->post("{$this->baseUrl}/api/restart-instance/{$instanceId}");

            if ($response->failed()) {
                Log::warning('Baileys Gateway restart instance failed', [
                    'instance_id' => $instanceId,
                    'status' => $response->status(),
                ]);

                throw new GatewayException(
                    "Failed to restart instance: HTTP {$response->status()}",
                    $response->body(),
                );
            }

            Log::info('Gateway instance restarted successfully', [
                'instance_id' => $instanceId,
            ]);
        } catch (GatewayException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Baileys Gateway restart instance exception', [
                'instance_id' => $instanceId,
                'error' => $e->getMessage(),
            ]);

            throw new GatewayException(
                'Failed to restart instance: ' . $e->getMessage(),
                $e->getMessage(),
            );
        }
    }

    /**
     * Validate webhook signature from Baileys Gateway.
     *
     * @param  string  $payload  The raw request body
     * @param  string  $signature  The signature from X-Baileys-Signature header
     */
    public static function validateWebhookSignature(string $payload, string $signature): bool
    {
        $secret = config('wa-automation.baileys.webhook_secret');
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }
}
