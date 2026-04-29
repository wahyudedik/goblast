<?php

namespace App\Console\Commands;

use App\Models\MessageLog;
use App\Models\SystemLog;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('log:cleanup')]
#[Description('Clean up old message logs and system logs')]
class CleanupLogs extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting log cleanup...');

        // Delete message_logs older than 90 days
        $messageLogCutoff = now()->subDays(90);
        $deletedMessageLogs = MessageLog::where('created_at', '<', $messageLogCutoff)->delete();

        $this->info("Deleted {$deletedMessageLogs} message log(s) older than 90 days");

        // Delete system_logs older than 180 days
        $systemLogCutoff = now()->subDays(180);
        $deletedSystemLogs = SystemLog::where('created_at', '<', $systemLogCutoff)->delete();

        $this->info("Deleted {$deletedSystemLogs} system log(s) older than 180 days");

        $summary = [
            'message_logs_deleted' => $deletedMessageLogs,
            'system_logs_deleted' => $deletedSystemLogs,
            'message_log_cutoff' => $messageLogCutoff->toDateTimeString(),
            'system_log_cutoff' => $systemLogCutoff->toDateTimeString(),
            'timestamp' => now()->toDateTimeString(),
        ];

        Log::info('Log cleanup completed', $summary);

        $this->newLine();
        $this->info('Log cleanup completed:');
        $this->table(
            ['Log Type', 'Deleted Count', 'Cutoff Date'],
            [
                ['Message Logs', $deletedMessageLogs, $messageLogCutoff->toDateString()],
                ['System Logs', $deletedSystemLogs, $systemLogCutoff->toDateString()],
            ]
        );

        return self::SUCCESS;
    }
}
