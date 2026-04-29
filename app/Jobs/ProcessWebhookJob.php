<?php

namespace App\Jobs;

use App\Models\Device;
use App\Services\Contracts\AutoReplyServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessWebhookJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $payload,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AutoReplyServiceInterface $autoReplyService): void
    {
        try {
            // Validate required payload fields
            if (! $this->validatePayload()) {
                Log::warning('Webhook payload validation failed', [
                    'payload' => $this->payload,
                ]);

                return;
            }

            $event = $this->payload['event'];
            $deviceId = $this->payload['device_id'];
            $from = $this->payload['from'];
            $message = $this->payload['message'];
            $timestamp = $this->payload['timestamp'] ?? null;

            // Find the device
            $device = Device::where('gateway_device_id', $deviceId)->first();

            if (! $device) {
                Log::warning('Device not found for webhook', [
                    'gateway_device_id' => $deviceId,
                    'payload' => $this->payload,
                ]);

                return;
            }

            // Calculate received_at from timestamp or use current time
            $receivedAt = $timestamp ? now()->setTimestamp((int) ($timestamp / 1000)) : now();

            // Process incoming message through AutoReplyService
            $autoReplyService->processIncomingMessage($deviceId, $from, $message);

            Log::info('Webhook processed successfully', [
                'event' => $event,
                'device_id' => $deviceId,
                'from' => $from,
                'tenant_id' => $device->tenant_id,
            ]);
        } catch (Throwable $e) {
            Log::error('Error processing webhook', [
                'payload' => $this->payload,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Validate webhook payload structure.
     */
    protected function validatePayload(): bool
    {
        $requiredFields = ['event', 'device_id', 'from', 'message'];

        foreach ($requiredFields as $field) {
            if (! isset($this->payload[$field]) || empty($this->payload[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('ProcessWebhookJob failed', [
            'payload' => $this->payload,
            'error' => $exception->getMessage(),
        ]);
    }
}
