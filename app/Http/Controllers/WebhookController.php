<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessWebhookJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle incoming webhook from Baileys Gateway.
     */
    public function baileys(Request $request): JsonResponse
    {
        try {
            // Validate webhook signature
            if (! $this->validateSignature($request)) {
                Log::warning('Invalid webhook signature', [
                    'ip' => $request->ip(),
                    'payload' => $request->all(),
                ]);

                return response()->json([
                    'error' => 'Invalid signature',
                ], 401);
            }

            // Get payload
            $payload = $request->all();

            // Validate payload structure
            if (! $this->validatePayloadStructure($payload)) {
                Log::warning('Malformed webhook payload', [
                    'payload' => $payload,
                ]);

                return response()->json([
                    'error' => 'Malformed payload',
                ], 400);
            }

            // Dispatch job to process webhook asynchronously
            ProcessWebhookJob::dispatch($payload);

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed',
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error handling webhook', [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
            ]);

            return response()->json([
                'error' => 'Internal server error',
            ], 500);
        }
    }

    /**
     * Handle incoming webhook from WAHA Gateway.
     */
    public function waha(Request $request): JsonResponse
    {
        try {
            // Validate X-Webhook-Token header
            $configToken = config('wa-automation.waha.webhook_token');

            // If config is null (env not set), reject immediately
            if ($configToken === null) {
                Log::warning('WAHA webhook token not configured', ['ip' => $request->ip()]);

                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $requestToken = $request->header('X-Webhook-Token', '');

            if (! hash_equals((string) $configToken, (string) $requestToken)) {
                Log::warning('Invalid WAHA webhook token', ['ip' => $request->ip()]);

                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // Validate required fields
            $payload = $request->all();

            if (empty($payload['event']) || empty($payload['session'])) {
                Log::warning('Malformed WAHA webhook payload', ['payload' => $payload]);

                return response()->json(['error' => 'Malformed payload'], 400);
            }

            // Normalize payload to internal format
            $normalizedPayload = $this->normalizeWahaPayload($payload);

            // Dispatch job
            ProcessWebhookJob::dispatch($normalizedPayload);

            return response()->json(['success' => true, 'message' => 'Webhook processed'], 200);
        } catch (\Throwable $e) {
            Log::error('Error handling WAHA webhook', [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Normalize WAHA webhook payload to internal format.
     *
     * WAHA event → internal event mapping:
     * - message → message (with from/message extraction)
     * - session.status (WORKING) → session.restore_complete
     * - session.status (FAILED) → device.manual_intervention (+ message field)
     * - other → event as-is
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function normalizeWahaPayload(array $payload): array
    {
        $event = $payload['event'];
        $session = $payload['session'];

        $normalized = [
            'event' => $event,
            'device_id' => $session,
        ];

        if ($event === 'message') {
            $from = $payload['payload']['from'] ?? '';
            // Strip @c.us suffix if present
            $from = str_replace('@c.us', '', $from);

            $normalized['from'] = $from;
            $normalized['message'] = $payload['payload']['body'] ?? '';
        } elseif ($event === 'session.status') {
            $status = $payload['payload']['status'] ?? '';

            if ($status === 'WORKING') {
                $normalized['event'] = 'session.restore_complete';
            } elseif ($status === 'FAILED') {
                $normalized['event'] = 'device.manual_intervention';
                $normalized['message'] = 'Device requires manual intervention';
            }
            // Other statuses: keep event as 'session.status'
        }

        return $normalized;
    }

    /**
     * Validate webhook signature using HMAC SHA-256.
     */
    protected function validateSignature(Request $request): bool
    {
        $signature = $request->header('X-Baileys-Signature');

        if (! $signature) {
            return false;
        }

        $secret = config('wa-automation.baileys.webhook_secret');
        $payload = $request->getContent();

        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Validate basic payload structure.
     */
    protected function validatePayloadStructure(array $payload): bool
    {
        $requiredFields = ['event', 'device_id', 'from', 'message'];

        foreach ($requiredFields as $field) {
            if (! isset($payload[$field]) || empty($payload[$field])) {
                return false;
            }
        }

        return true;
    }
}
