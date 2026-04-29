<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Models\MessageLog;
use App\Models\Tenant;
use App\Notifications\TrialExpiredNotification;
use App\Notifications\TrialExpiringNotification;
use App\Services\Contracts\MessageServiceInterface;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

#[Signature('trial:check-expiry')]
#[Description('Check for expiring and expired trial accounts')]
class CheckTrialExpiry extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(MessageServiceInterface $messageService): int
    {
        $this->info('Starting trial expiry check...');

        // Query trials that will expire in 3 days
        $expiringIn3Days = Tenant::where('status', 'trial')
            ->whereDate('trial_ends_at', '=', now()->addDays(3)->toDateString())
            ->get();

        $this->info("Found {$expiringIn3Days->count()} trial(s) expiring in 3 days");

        foreach ($expiringIn3Days as $tenant) {
            $this->sendExpiryNotifications($tenant, 3, $messageService);
        }

        // Query trials that will expire in 1 day
        $expiringIn1Day = Tenant::where('status', 'trial')
            ->whereDate('trial_ends_at', '=', now()->addDay()->toDateString())
            ->get();

        $this->info("Found {$expiringIn1Day->count()} trial(s) expiring in 1 day");

        foreach ($expiringIn1Day as $tenant) {
            $this->sendExpiryNotifications($tenant, 1, $messageService);
        }

        // Query trials that have already expired
        $expired = Tenant::where('status', 'trial')
            ->where('trial_ends_at', '<', now())
            ->get();

        $this->info("Found {$expired->count()} expired trial(s)");

        foreach ($expired as $tenant) {
            // Auto-suspend tenant account
            $tenant->update([
                'status' => 'suspended',
                'suspended_at' => now(),
                'suspended_reason' => 'Trial period expired',
            ]);

            $this->warn("Suspended trial account for {$tenant->name} (expired: {$tenant->trial_ends_at->toDateString()})");

            // Send email notification
            $primaryUser = $tenant->users()->where('role', 'admin')->first();

            if ($primaryUser) {
                try {
                    $primaryUser->notify(new TrialExpiredNotification($tenant));
                    $this->info("Sent trial expired notification to {$tenant->name}");
                } catch (\Throwable $e) {
                    $this->error("Failed to send email to {$tenant->name}: {$e->getMessage()}");
                    Log::error('Failed to send trial expired notification', [
                        'tenant_id' => $tenant->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Send WhatsApp notification if device is available
            $this->sendWhatsAppNotification($tenant, 'expired', $messageService);
        }

        $summary = [
            'expiring_in_3_days' => $expiringIn3Days->count(),
            'expiring_in_1_day' => $expiringIn1Day->count(),
            'expired' => $expired->count(),
            'timestamp' => now()->toDateTimeString(),
        ];

        Log::info('Trial expiry check completed', $summary);

        $this->newLine();
        $this->info('Trial expiry check completed:');
        $this->table(
            ['Status', 'Count'],
            [
                ['Expiring in 3 days', $summary['expiring_in_3_days']],
                ['Expiring in 1 day', $summary['expiring_in_1_day']],
                ['Expired', $summary['expired']],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * Send expiry notifications via email and WhatsApp.
     */
    protected function sendExpiryNotifications(Tenant $tenant, int $daysRemaining, MessageServiceInterface $messageService): void
    {
        // Send email notification
        $primaryUser = $tenant->users()->where('role', 'admin')->first();

        if ($primaryUser) {
            try {
                $primaryUser->notify(new TrialExpiringNotification($tenant, $daysRemaining));
                $this->info("Sent trial expiring notification to {$tenant->name} ({$daysRemaining} days remaining)");
            } catch (\Throwable $e) {
                $this->error("Failed to send email to {$tenant->name}: {$e->getMessage()}");
                Log::error('Failed to send trial expiring notification', [
                    'tenant_id' => $tenant->id,
                    'days_remaining' => $daysRemaining,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Send WhatsApp notification
        $this->sendWhatsAppNotification($tenant, "expiring_{$daysRemaining}_days", $messageService);
    }

    /**
     * Send WhatsApp notification to tenant.
     */
    protected function sendWhatsAppNotification(Tenant $tenant, string $type, MessageServiceInterface $messageService): void
    {
        // Get first connected device for the tenant
        $device = $tenant->devices()->where('status', 'connected')->first();

        if (! $device || ! $tenant->phone) {
            return;
        }

        $message = match ($type) {
            'expiring_3_days' => "Halo {$tenant->name},\n\nMasa trial Anda akan berakhir dalam 3 hari pada {$tenant->trial_ends_at->format('d/m/Y')}.\n\nSegera upgrade ke paket berbayar untuk melanjutkan layanan.\n\nHubungi: wa.me/6281529211963",
            'expiring_1_days' => "Halo {$tenant->name},\n\nMasa trial Anda akan berakhir BESOK pada {$tenant->trial_ends_at->format('d/m/Y')}.\n\nSegera upgrade untuk menghindari penangguhan akun.\n\nHubungi: wa.me/6281529211963",
            'expired' => "Halo {$tenant->name},\n\nMasa trial Anda telah berakhir dan akun Anda telah ditangguhkan.\n\nUntuk mengaktifkan kembali, silakan upgrade ke paket berbayar.\n\nHubungi: wa.me/6281529211963",
            default => null,
        };

        if (! $message) {
            return;
        }

        try {
            // Create message log
            $messageLog = MessageLog::create([
                'tenant_id' => $tenant->id,
                'device_id' => $device->id,
                'recipient' => $tenant->phone,
                'message' => $message,
                'status' => 'pending',
                'source' => 'trigger',
                'job_id' => Str::uuid()->toString(),
            ]);

            // Dispatch message job
            $messageService->dispatchJob($messageLog);

            $this->info("Sent WhatsApp notification to {$tenant->name} ({$tenant->phone})");
        } catch (\Throwable $e) {
            $this->error("Failed to send WhatsApp to {$tenant->name}: {$e->getMessage()}");
            Log::error('Failed to send trial WhatsApp notification', [
                'tenant_id' => $tenant->id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
