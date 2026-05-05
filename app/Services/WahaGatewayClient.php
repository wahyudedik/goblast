<?php

namespace App\Services;

use App\Exceptions\GatewayException;
use App\Services\Contracts\GatewayClientInterface;
use App\Services\ValueObjects\GatewayResponse;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WahaGatewayClient implements GatewayClientInterface
{
    private const TIMEOUT = 30;

    private const CONNECT_TIMEOUT = 10;

    private const MAX_RETRIES = 3;

    private const RETRY_DELAYS_MS = [1000, 2000, 4000]; // exponential backoff in milliseconds

    private const QR_POLL_MAX_ATTEMPTS = 5;

    private const QR_POLL_INTERVAL_MS = 500;

    public function __construct(
        private string $baseUrl = '',
        private string $apiKey = '',
    ) {
        $this->baseUrl = $baseUrl ?: (string) config('wa-automation.waha.base_url', '');
        $this->apiKey = $apiKey ?: (string) config('wa-automation.waha.api_key', '');
    }

    /**
     * Convert a phone number to WAHA chatId format.
     *
     * Strips leading '+' and appends '@c.us' if not already present.
     * Examples:
     *   '+628123456789'      → '628123456789@c.us'
     *   '628123456789'       → '628123456789@c.us'
     *   '628123456789@c.us'  → '628123456789@c.us'
     *   '+628123456789@c.us' → '628123456789@c.us'
     */
    private function toChatId(string $phoneNumber): string
    {
        // Remove leading '+'
        $number = ltrim($phoneNumber, '+');

        // Append '@c.us' if not already present
        if (! str_ends_with($number, '@c.us')) {
            $number .= '@c.us';
        }

        return $number;
    }

    /**
     * Build an HTTP client with the standard headers and timeouts.
     */
    private function httpClient(): PendingRequest
    {
        return Http::withHeaders(['X-Api-Key' => $this->apiKey])
            ->timeout(self::TIMEOUT)
            ->connectTimeout(self::CONNECT_TIMEOUT);
    }

    /**
     * Send a WhatsApp message via WAHA API.
     *
     * Retries up to 3 times with exponential backoff (1s, 2s, 4s) on network errors.
     *
     * @throws GatewayException
     */
    public function sendMessage(string $sessionName, string $to, string $message): GatewayResponse
    {
        $chatId = $this->toChatId($to);
        $attempt = 0;
        $lastException = null;

        while ($attempt < self::MAX_RETRIES) {
            try {
                $response = $this->httpClient()
                    ->post("{$this->baseUrl}/api/sendText", [
                        'session' => $sessionName,
                        'chatId' => $chatId,
                        'text' => $message,
                    ]);

                if ($response->successful()) {
                    Log::info('WAHA message sent successfully', [
                        'session' => $sessionName,
                        'chatId' => $chatId,
                    ]);

                    return new GatewayResponse(success: true, status: 'sent');
                }

                Log::warning('WAHA sendMessage failed', [
                    'session' => $sessionName,
                    'chatId' => $chatId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new GatewayException(
                    "Failed to send message: HTTP {$response->status()}",
                    $response->body(),
                );
            } catch (GatewayException $e) {
                // HTTP errors are not retried — rethrow immediately
                throw $e;
            } catch (\Exception $e) {
                // Network/connection errors — retry with backoff
                $lastException = $e;
                $attempt++;

                if ($attempt < self::MAX_RETRIES) {
                    $delayMs = self::RETRY_DELAYS_MS[$attempt - 1] ?? 4000;
                    usleep($delayMs * 1000);
                }
            }
        }

        Log::error('WAHA sendMessage failed after retries', [
            'session' => $sessionName,
            'chatId' => $chatId,
            'error' => $lastException?->getMessage(),
        ]);

        throw new GatewayException(
            'Failed to send message after retries: '.($lastException?->getMessage() ?? 'Unknown error'),
            $lastException?->getMessage(),
        );
    }

    /**
     * Get a QR code for a WAHA session.
     *
     * This method is blocking (polling up to 5 times with 500ms intervals).
     * This is acceptable because it is called from an async job or controller.
     *
     * Steps:
     * 1. Create session via POST /api/sessions (idempotent — 422 "exists"/"already" is treated as success)
     * 2. Start session via POST /api/sessions/{name}/start
     * 3. Poll GET /api/sessions/{name} until status = 'SCAN_QR_CODE' (max 5 attempts, 500ms interval)
     * 4. Fetch QR code via GET /api/{name}/auth/qr?format=base64 and return the 'value' field
     *
     * @throws GatewayException
     */
    public function getQrCode(string $sessionName): string
    {
        $webhookUrl = (string) config('wa-automation.waha.webhook_url', '');

        // Step 1: Create session (idempotent)
        $createResponse = $this->httpClient()
            ->post("{$this->baseUrl}/api/sessions", [
                'name' => $sessionName,
                'config' => [
                    'webhooks' => [
                        [
                            'url' => $webhookUrl,
                            'events' => ['message', 'session.status'],
                        ],
                    ],
                ],
            ]);

        if (! $createResponse->successful()) {
            $body = $createResponse->body();

            // HTTP 422 with "exists" or "already" in body → idempotent, treat as success
            if ($createResponse->status() === 422) {
                $bodyLower = strtolower($body);
                if (str_contains($bodyLower, 'exists') || str_contains($bodyLower, 'already')) {
                    Log::info('WAHA session already exists, continuing', ['session' => $sessionName]);
                } else {
                    throw new GatewayException(
                        'Failed to create WAHA session: HTTP 422',
                        $body,
                    );
                }
            } else {
                throw new GatewayException(
                    "Failed to create WAHA session: HTTP {$createResponse->status()}",
                    $body,
                );
            }
        }

        // Step 2: Start session
        $startResponse = $this->httpClient()
            ->post("{$this->baseUrl}/api/sessions/{$sessionName}/start");

        if (! $startResponse->successful()) {
            throw new GatewayException(
                "Failed to start WAHA session: HTTP {$startResponse->status()}",
                $startResponse->body(),
            );
        }

        // Step 3: Poll until status = SCAN_QR_CODE
        $reached = false;

        for ($i = 0; $i < self::QR_POLL_MAX_ATTEMPTS; $i++) {
            if ($i > 0) {
                usleep(self::QR_POLL_INTERVAL_MS * 1000);
            }

            $statusResponse = $this->httpClient()
                ->get("{$this->baseUrl}/api/sessions/{$sessionName}");

            if ($statusResponse->successful()) {
                $status = $statusResponse->json('status');

                if ($status === 'SCAN_QR_CODE') {
                    $reached = true;
                    break;
                }
            }
        }

        if (! $reached) {
            throw new GatewayException('Session not ready for QR code');
        }

        // Step 4: Fetch QR code
        $qrResponse = $this->httpClient()
            ->get("{$this->baseUrl}/api/{$sessionName}/auth/qr", ['format' => 'base64']);

        if (! $qrResponse->successful()) {
            throw new GatewayException(
                "Failed to get QR code: HTTP {$qrResponse->status()}",
                $qrResponse->body(),
            );
        }

        $value = $qrResponse->json('value');

        if ($value === null) {
            throw new GatewayException('QR code value not found in response', $qrResponse->body());
        }

        return $value;
    }

    /**
     * Get the current connection status of a WAHA session.
     *
     * @return string One of: 'connected', 'disconnected', 'error'
     */
    public function getConnectionStatus(string $sessionName): string
    {
        try {
            $response = $this->httpClient()
                ->get("{$this->baseUrl}/api/sessions/{$sessionName}");

            if (! $response->successful()) {
                return 'disconnected';
            }

            $status = $response->json('status');

            return $status === 'WORKING' ? 'connected' : 'disconnected';
        } catch (\Exception $e) {
            Log::error('WAHA getConnectionStatus exception', [
                'session' => $sessionName,
                'error' => $e->getMessage(),
            ]);

            return 'error';
        }
    }

    /**
     * Disconnect a WAHA session: stop it then delete it.
     *
     * @throws GatewayException
     */
    public function disconnectDevice(string $sessionName): void
    {
        $stopResponse = $this->httpClient()
            ->post("{$this->baseUrl}/api/sessions/{$sessionName}/stop");

        if (! $stopResponse->successful()) {
            throw new GatewayException(
                "Failed to stop WAHA session: HTTP {$stopResponse->status()}",
                $stopResponse->body(),
            );
        }

        $deleteResponse = $this->httpClient()
            ->delete("{$this->baseUrl}/api/sessions/{$sessionName}");

        if (! $deleteResponse->successful()) {
            throw new GatewayException(
                "Failed to delete WAHA session: HTTP {$deleteResponse->status()}",
                $deleteResponse->body(),
            );
        }

        Log::info('WAHA session disconnected and deleted', ['session' => $sessionName]);
    }

    /**
     * Restart a WAHA session.
     *
     * @throws GatewayException
     */
    public function restartInstance(string $sessionName): void
    {
        $response = $this->httpClient()
            ->post("{$this->baseUrl}/api/sessions/{$sessionName}/restart");

        if (! $response->successful()) {
            throw new GatewayException(
                "Failed to restart WAHA session: HTTP {$response->status()}",
                $response->body(),
            );
        }

        Log::info('WAHA session restarted', ['session' => $sessionName]);
    }
}
