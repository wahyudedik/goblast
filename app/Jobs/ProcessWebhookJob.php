<?php

namespace App\Jobs;

use App\Models\Device;
use App\Models\SystemLog;
use App\Services\Contracts\AlertServiceInterface;
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
    public function handle(AutoReplyServiceInterface $autoReplyService, AlertServiceInterface $alertService): void
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

            match ($event) {
                'session.restore_complete' => $this->handleSessionRestoreComplete(),
                'device.manual_intervention' => $this->handleDeviceManualIntervention($alertService),
                default => $this->handleDefault($autoReplyService),
            };
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
     * Handle session.restore_complete event by logging restore stats.
     */
    protected function handleSessionRestoreComplete(): void
    {
        $stats = $this->payload['stats'] ?? [];

        SystemLog::create([
            'type' => 'gateway',
            'severity' => 'info',
            'message' => $this->payload['message'] ?? 'Session restoration completed',
            'context' => [
                'event' => 'session.restore_complete',
                'stats' => $stats,
                'timestamp' => $this->payload['timestamp'] ?? now()->toIso8601String(),
            ],
        ]);

        Log::info('Session restore complete webhook processed', [
            'stats' => $stats,
        ]);
    }

    /**
     * Handle device.manual_intervention event by updating device status and creating an alert.
     */
    protected function handleDeviceManualIntervention(AlertServiceInterface $alertService): void
    {
        $deviceId = $this->payload['device_id'];

        $device = Device::where('gateway_device_id', $deviceId)->first();

        if (! $device) {
            Log::warning('Device not found for manual intervention webhook', [
                'gateway_device_id' => $deviceId,
                'payload' => $this->payload,
            ]);

            return;
        }

        $device->update([
            'status' => 'error',
        ]);

        $alertService->create(
            type: 'gateway.down',
            message: $this->payload['message'] ?? 'Device requires manual intervention',
            severity: 'critical',
            tenant: $device->tenant,
            context: [
                'device_id' => $device->id,
                'gateway_device_id' => $deviceId,
                'status' => $this->payload['status'] ?? 'manual_intervention_required',
                'failure_count' => $this->payload['failure_count'] ?? null,
                'last_error' => $this->payload['last_error'] ?? null,
            ],
        );

        Log::info('Device manual intervention webhook processed', [
            'device_id' => $device->id,
            'gateway_device_id' => $deviceId,
            'tenant_id' => $device->tenant_id,
        ]);
    }

    /**
     * Handle default webhook events (e.g., message) via AutoReplyService.
     */
    protected function handleDefault(AutoReplyServiceInterface $autoReplyService): void
    {
        $deviceId = $this->payload['device_id'];

        // Guard: 'from' and 'message' are required for message processing
        if (empty($this->payload['from']) || empty($this->payload['message'])) {
            Log::warning('Webhook handleDefault missing from or message fields', [
                'event' => $this->payload['event'],
                'device_id' => $deviceId,
            ]);

            return;
        }

        $from = $this->payload['from'];
        $message = $this->payload['message'];

        $device = Device::where('gateway_device_id', $deviceId)->first();

        if (! $device) {
            Log::warning('Device not found for webhook', [
                'gateway_device_id' => $deviceId,
                'payload' => $this->payload,
            ]);

            return;
        }

        $autoReplyService->processIncomingMessage($deviceId, $from, $message);

        Log::info('Webhook processed successfully', [
            'event' => $this->payload['event'],
            'device_id' => $deviceId,
            'from' => $from,
            'tenant_id' => $device->tenant_id,
        ]);
    }

    /**
     * Validate webhook payload structure.
     *
     * All events require 'event' and 'device_id'.
     * The 'message' event additionally requires 'from' and 'message'.
     */
    protected function validatePayload(): bool
    {
        // All events require 'event' and 'device_id'
        if (empty($this->payload['event']) || empty($this->payload['device_id'])) {
            return false;
        }

        // 'message' event additionally requires 'from' and 'message'
        if ($this->payload['event'] === 'message') {
            if (empty($this->payload['from']) || empty($this->payload['message'])) {
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
