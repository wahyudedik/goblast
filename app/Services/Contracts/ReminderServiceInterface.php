<?php

namespace App\Services\Contracts;

use App\Models\Reminder;
use Illuminate\Support\Collection;

interface ReminderServiceInterface
{
    /**
     * Process all active reminders by dispatching SendReminderJob for each.
     *
     * @return array{processed: int, skipped: int}
     */
    public function processReminders(): array;

    /**
     * Check if a reminder's condition is met based on its type.
     *
     * @return bool True if the reminder should be processed
     */
    public function checkCondition(Reminder $reminder): bool;

    /**
     * Send a reminder by dispatching SendReminderJob.
     */
    public function sendReminder(Reminder $reminder): void;

    /**
     * Get recipients that match the reminder's condition.
     *
     * @return Collection<int, array{phone: string, condition_key: string, context: array<string, mixed>}>
     */
    public function getRecipients(Reminder $reminder): Collection;
}
