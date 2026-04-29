<?php

namespace App\Console\Commands;

use App\Models\Broadcast;
use App\Services\Contracts\BroadcastServiceInterface;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('broadcast:dispatch-scheduled')]
#[Description('Dispatch all scheduled broadcasts that are due')]
class DispatchScheduledBroadcasts extends Command
{
    public function handle(BroadcastServiceInterface $broadcastService): int
    {
        $this->info('Checking for scheduled broadcasts...');

        $broadcasts = Broadcast::with(['tenant', 'device'])
            ->where('status', 'draft')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->get();

        if ($broadcasts->isEmpty()) {
            $this->info('No scheduled broadcasts due.');

            return self::SUCCESS;
        }

        $this->info("Found {$broadcasts->count()} scheduled broadcast(s) to dispatch.");

        $dispatched = 0;
        $failed = 0;

        foreach ($broadcasts as $broadcast) {
            // Check device is still connected
            if ($broadcast->device->status !== 'connected') {
                $this->warn("Skipping '{$broadcast->name}' (ID: {$broadcast->id}): Device not connected");
                $broadcast->update(['status' => 'failed']);
                $failed++;

                continue;
            }

            try {
                $broadcastService->dispatch($broadcast);
                $dispatched++;
                $this->info("Dispatched '{$broadcast->name}' (ID: {$broadcast->id})");
            } catch (\Exception $e) {
                $failed++;
                $broadcast->update(['status' => 'failed']);
                $this->error("Failed '{$broadcast->name}' (ID: {$broadcast->id}): {$e->getMessage()}");
                Log::error('Scheduled broadcast dispatch failed', [
                    'broadcast_id' => $broadcast->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Scheduled broadcast processing completed', [
            'total' => $broadcasts->count(),
            'dispatched' => $dispatched,
            'failed' => $failed,
        ]);

        $this->newLine();
        $this->info("Done: {$dispatched} dispatched, {$failed} failed.");

        return self::SUCCESS;
    }
}
