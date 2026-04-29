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
