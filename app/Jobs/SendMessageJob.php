<?php

namespace App\Jobs;

use App\Models\Alert;
use App\Models\MessageLog;
use App\Services\Contracts\BaileysGatewayClientInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendMessageJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int> */
    public array $backoff = [30, 60, 120];

    public int $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public MessageLog $messageLog,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(BaileysGatewayClientInterface $client): void
    {
        // Validate subscription status before sending
        $subscription = $this->messageLog->tenant->subscriptions()
            ->where('status', 'active')
            ->first();

        if (! $subscription) {
            $this->messageLog->update(['status' => 'cancelled']);

            return;
        }

        try {
            // Send message via Baileys Gateway
            $response = $client->sendMessage(
                $this->messageLog->device->gateway_device_id,
                $this->messageLog->recipient,
                $this->messageLog->message
            );

            // Check if message was sent successfully
            if ($response->success && $response->status === 'sent') {
                $this->messageLog->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'attempts' => $this->attempts(),
                ]);
            } else {
                throw new \Exception($response->errorMessage ?? 'Unknown error from gateway');
            }
        } catch (Throwable $e) {
            // Update message log with error details
            $this->messageLog->update([
                'error_message' => $e->getMessage(),
                'attempts' => $this->attempts(),
            ]);

            // If max retries exceeded, mark as failed
            if ($this->attempts() >= $this->tries) {
                $this->messageLog->update([
                    'status' => 'failed',
                    'failed_at' => now(),
                ]);

                // Create alert for permanent failure
                Alert::create([
                    'tenant_id' => $this->messageLog->tenant_id,
                    'type' => 'job.failed_permanent',
                    'severity' => 'error',
                    'message' => "Message to {$this->messageLog->recipient} failed permanently after {$this->tries} attempts",
                    'context' => [
                        'message_log_id' => $this->messageLog->id,
                        'recipient' => $this->messageLog->recipient,
                        'device_id' => $this->messageLog->device_id,
                        'error' => $e->getMessage(),
                        'attempts' => $this->attempts(),
                    ],
                    'status' => 'active',
                ]);

                Log::error('Message job failed permanently', [
                    'message_log_id' => $this->messageLog->id,
                    'recipient' => $this->messageLog->recipient,
                    'error' => $e->getMessage(),
                ]);
            } else {
                // Mark as retrying for next attempt
                $this->messageLog->update(['status' => 'retrying']);
                throw $e;
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        $this->messageLog->update([
            'status' => 'failed',
            'failed_at' => now(),
            'error_message' => $exception->getMessage(),
        ]);

        Log::error('SendMessageJob failed', [
            'message_log_id' => $this->messageLog->id,
            'recipient' => $this->messageLog->recipient,
            'error' => $exception->getMessage(),
        ]);
    }
}
