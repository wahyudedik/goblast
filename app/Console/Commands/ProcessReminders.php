<?php

namespace App\Console\Commands;

use App\Jobs\SendReminderJob;
use App\Models\Reminder;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('reminder:process')]
#[Description('Process all active reminders and dispatch jobs based on their schedule')]
class ProcessReminders extends Command
{
    public function handle(): int
    {
        $this->info('Checking reminders...');

        $now = Carbon::now(config('app.timezone'));
        $currentTime = $now->format('H:i');
        $currentDayOfWeek = $now->dayOfWeekIso; // 1=Monday, 7=Sunday
        $currentDayOfMonth = $now->day;
        $currentMonth = $now->month;

        $reminders = Reminder::with(['tenant', 'device', 'template'])
            ->where('is_active', true)
            ->where('send_time', $currentTime)
            ->get();

        if ($reminders->isEmpty()) {
            $this->info("No reminders scheduled for {$currentTime}.");

            return self::SUCCESS;
        }

        $dispatched = 0;
        $skipped = 0;

        foreach ($reminders as $reminder) {
            // Check if this reminder should run based on frequency
            if (! $this->shouldRun($reminder, $currentDayOfWeek, $currentDayOfMonth, $currentMonth)) {
                $skipped++;

                continue;
            }

            // Check device is connected
            if ($reminder->device->status !== 'connected') {
                $this->warn("Skipping '{$reminder->name}': Device not connected");
                $skipped++;

                continue;
            }

            // Check tenant has active subscription
            if (! $reminder->tenant->subscriptions()->where('status', 'active')->exists()) {
                $this->warn("Skipping '{$reminder->name}': No active subscription");
                $skipped++;

                continue;
            }

            // Prevent duplicate runs (check if already ran today)
            if ($reminder->last_run_at && $reminder->last_run_at->isToday()) {
                $this->info("Skipping '{$reminder->name}': Already ran today");
                $skipped++;

                continue;
            }

            // Dispatch the job
            SendReminderJob::dispatch($reminder);
            $reminder->update(['last_run_at' => now()]);
            $dispatched++;

            $this->info("Dispatched '{$reminder->name}' ({$reminder->frequency}, {$reminder->send_time})");
        }

        Log::info('Reminder processing completed', [
            'time' => $currentTime,
            'checked' => $reminders->count(),
            'dispatched' => $dispatched,
            'skipped' => $skipped,
        ]);

        $this->info("Done: {$dispatched} dispatched, {$skipped} skipped.");

        return self::SUCCESS;
    }

    /**
     * Check if a reminder should run based on its frequency and current date.
     */
    private function shouldRun(Reminder $reminder, int $dayOfWeek, int $dayOfMonth, int $month): bool
    {
        return match ($reminder->frequency) {
            'daily' => true,
            'weekly' => $reminder->send_day === $dayOfWeek,
            'monthly' => $reminder->send_day === $dayOfMonth,
            'yearly' => $reminder->send_day === $month,
            default => false,
        };
    }
}
