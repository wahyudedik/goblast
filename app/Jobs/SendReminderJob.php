<?php

namespace App\Jobs;

use App\Models\Contact;
use App\Models\MessageLog;
use App\Models\Reminder;
use App\Models\ReminderLog;
use App\Services\Contracts\MessageServiceInterface;
use App\Services\Contracts\QuotaServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SendReminderJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Reminder $reminder,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(MessageServiceInterface $messageService, QuotaServiceInterface $quotaService): void
    {
        // Check if reminder is still active
        if (! $this->reminder->is_active) {
            Log::info('Reminder is not active, skipping', [
                'reminder_id' => $this->reminder->id,
            ]);

            return;
        }

        // Check if device is connected
        if ($this->reminder->device->status !== 'connected') {
            Log::warning('Reminder device is not connected', [
                'reminder_id' => $this->reminder->id,
                'device_id' => $this->reminder->device_id,
                'device_status' => $this->reminder->device->status,
            ]);

            return;
        }

        // Get recipients based on reminder type
        $recipients = $this->getRecipientsForReminderType();

        // Update reminder last_run_at regardless of whether recipients were found
        $this->reminder->update(['last_run_at' => now()]);

        if ($recipients->isEmpty()) {
            Log::info('No recipients found for reminder', [
                'reminder_id' => $this->reminder->id,
                'type' => $this->reminder->type,
            ]);

            return;
        }

        $sentCount = 0;
        $skippedCount = 0;

        foreach ($recipients as $recipient) {
            // Check if already sent within 24 hours
            if ($this->isDuplicate($recipient['phone'], $recipient['condition_key'])) {
                $skippedCount++;

                continue;
            }

            // Check quota availability
            if ($quotaService->isExhausted($this->reminder->tenant)) {
                Log::warning('Quota exhausted for reminder', [
                    'reminder_id' => $this->reminder->id,
                    'tenant_id' => $this->reminder->tenant_id,
                    'sent_count' => $sentCount,
                    'skipped_count' => $skippedCount,
                ]);
                break;
            }

            try {
                // Render template with recipient context, or use manual message
                $message = $this->reminder->template
                    ? $messageService->renderTemplate($this->reminder->template, $recipient['context'])
                    : ($this->reminder->message ?? $this->reminder->name);

                // Create message log
                $messageLog = MessageLog::create([
                    'tenant_id' => $this->reminder->tenant_id,
                    'device_id' => $this->reminder->device_id,
                    'reminder_id' => $this->reminder->id,
                    'template_id' => $this->reminder->template_id,
                    'recipient' => $recipient['phone'],
                    'message' => $message,
                    'status' => 'pending',
                    'source' => 'reminder',
                    'job_id' => Str::uuid()->toString(),
                ]);

                // Decrement quota
                $quotaService->decrement($this->reminder->tenant);

                // Dispatch SendMessageJob with random delay (5-10 seconds)
                $delay = rand(5, 10);
                $messageService->dispatchJob($messageLog, $delay);

                // Create reminder log to prevent duplicates
                ReminderLog::create([
                    'reminder_id' => $this->reminder->id,
                    'recipient' => $recipient['phone'],
                    'condition_key' => $recipient['condition_key'],
                    'sent_at' => now(),
                ]);

                $sentCount++;
            } catch (Throwable $e) {
                Log::error('Failed to send reminder message', [
                    'reminder_id' => $this->reminder->id,
                    'recipient' => $recipient['phone'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Reminder processing completed', [
            'reminder_id' => $this->reminder->id,
            'type' => $this->reminder->type,
            'total_recipients' => $recipients->count(),
            'sent_count' => $sentCount,
            'skipped_count' => $skippedCount,
        ]);
    }

    /**
     * Check if reminder was already sent to this recipient within 24 hours.
     */
    protected function isDuplicate(string $phone, string $conditionKey): bool
    {
        return ReminderLog::where('reminder_id', $this->reminder->id)
            ->where('recipient', $phone)
            ->where('condition_key', $conditionKey)
            ->where('sent_at', '>=', now()->subHours(24))
            ->exists();
    }

    /**
     * Get recipients based on reminder type.
     * Uses the stored recipients list from the reminder.
     *
     * @return Collection<int, array{phone: string, condition_key: string, context: array<string, mixed>}>
     */
    protected function getRecipientsForReminderType(): Collection
    {
        $recipients = $this->reminder->recipients ?? [];

        if (empty($recipients)) {
            return collect();
        }

        $conditionPrefix = match ($this->reminder->type) {
            'spp_due' => 'spp',
            'invoice_unpaid' => 'invoice',
            'booking_tomorrow' => 'booking',
            default => 'reminder',
        };

        return collect($recipients)->map(function ($phone) use ($conditionPrefix) {
            // Auto-save to contacts
            Contact::firstOrCreate(
                ['tenant_id' => $this->reminder->tenant_id, 'phone_number' => $phone],
                ['name' => null]
            );

            return [
                'phone' => $phone,
                'condition_key' => "{$conditionPrefix}_".now()->format('Y-m-d'),
                'context' => [
                    'nama' => $phone,
                    'tanggal' => now()->format('d/m/Y'),
                ],
            ];
        });
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('SendReminderJob failed', [
            'reminder_id' => $this->reminder->id,
            'type' => $this->reminder->type,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
