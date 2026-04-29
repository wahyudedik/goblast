<?php

namespace App\Services;

use App\Jobs\SendReminderJob;
use App\Models\Reminder;
use App\Models\ReminderLog;
use App\Services\Contracts\ReminderServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ReminderService implements ReminderServiceInterface
{
    /**
     * Valid reminder types supported by the system.
     *
     * @var array<int, string>
     */
    public const array VALID_TYPES = [
        'spp_due',
        'invoice_unpaid',
        'booking_tomorrow',
    ];

    /**
     * Process all active reminders by dispatching SendReminderJob for each.
     *
     * Queries all active reminders with connected devices and dispatches
     * a job for each one that passes the condition check.
     *
     * @return array{processed: int, skipped: int}
     */
    public function processReminders(): array
    {
        $reminders = Reminder::with(['device', 'tenant', 'template'])
            ->where('is_active', true)
            ->whereHas('device', function ($query) {
                $query->where('status', 'connected');
            })
            ->get();

        $processed = 0;
        $skipped = 0;

        foreach ($reminders as $reminder) {
            if (! $this->checkCondition($reminder)) {
                $skipped++;
                Log::info('Reminder condition not met, skipping', [
                    'reminder_id' => $reminder->id,
                    'type' => $reminder->type,
                ]);

                continue;
            }

            $this->sendReminder($reminder);
            $processed++;
        }

        Log::info('Reminder processing summary', [
            'total_reminders' => $reminders->count(),
            'processed' => $processed,
            'skipped' => $skipped,
        ]);

        return [
            'processed' => $processed,
            'skipped' => $skipped,
        ];
    }

    /**
     * Check if a reminder's condition is met based on its type.
     *
     * For now, all active reminders with connected devices pass the condition.
     * In a real implementation, this would check:
     * - spp_due: Check if there are SPP payments due today
     * - invoice_unpaid: Check if there are unpaid invoices
     * - booking_tomorrow: Check if there are bookings for tomorrow
     *
     * @return bool True if the reminder should be processed
     */
    public function checkCondition(Reminder $reminder): bool
    {
        // Check if reminder is active
        if (! $reminder->is_active) {
            return false;
        }

        // Check if device is connected
        if ($reminder->device->status !== 'connected') {
            return false;
        }

        // Check if tenant subscription is active
        $activeSubscription = $reminder->tenant->subscriptions()
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->exists();

        if (! $activeSubscription) {
            return false;
        }

        // Check if reminder has recipients
        $recipients = $this->getRecipients($reminder);
        if ($recipients->isEmpty()) {
            return false;
        }

        // Check if there are any recipients that haven't been sent to in 24 hours
        $hasUnsentRecipients = $recipients->contains(function ($recipient) use ($reminder) {
            return ! $this->isDuplicate($reminder, $recipient['phone'], $recipient['condition_key']);
        });

        return $hasUnsentRecipients;
    }

    /**
     * Send a reminder by dispatching SendReminderJob.
     */
    public function sendReminder(Reminder $reminder): void
    {
        SendReminderJob::dispatch($reminder);

        Log::info('SendReminderJob dispatched', [
            'reminder_id' => $reminder->id,
            'type' => $reminder->type,
            'tenant_id' => $reminder->tenant_id,
        ]);
    }

    /**
     * Get recipients that match the reminder's condition.
     *
     * Uses the stored recipients list from the reminder model.
     * In a real implementation, this could query external data sources
     * based on the reminder type.
     *
     * @return Collection<int, array{phone: string, condition_key: string, context: array<string, mixed>}>
     */
    public function getRecipients(Reminder $reminder): Collection
    {
        $recipients = $reminder->recipients ?? [];

        if (empty($recipients)) {
            return collect();
        }

        $conditionPrefix = match ($reminder->type) {
            'spp_due' => 'spp',
            'invoice_unpaid' => 'invoice',
            'booking_tomorrow' => 'booking',
            default => 'reminder',
        };

        return collect($recipients)->map(function ($phone) use ($conditionPrefix) {
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
     * Check if reminder was already sent to this recipient within 24 hours.
     */
    protected function isDuplicate(Reminder $reminder, string $phone, string $conditionKey): bool
    {
        return ReminderLog::where('reminder_id', $reminder->id)
            ->where('recipient', $phone)
            ->where('condition_key', $conditionKey)
            ->where('sent_at', '>=', now()->subHours(24))
            ->exists();
    }
}
