<?php

namespace App\Services;

use App\Exceptions\QuotaExceededException;
use App\Models\Broadcast;
use App\Models\Contact;
use App\Models\Device;
use App\Models\MessageLog;
use App\Models\Template;
use App\Models\Tenant;
use App\Services\Contracts\BroadcastServiceInterface;
use App\Services\Contracts\MessageServiceInterface;
use App\Services\Contracts\QuotaServiceInterface;
use App\Services\ValueObjects\BroadcastProgress;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BroadcastService implements BroadcastServiceInterface
{
    public function __construct(
        protected QuotaServiceInterface $quotaService,
        protected MessageServiceInterface $messageService,
    ) {}

    /**
     * Create a broadcast from a CSV file.
     *
     * @throws \InvalidArgumentException
     */
    public function createFromCsv(Tenant $tenant, UploadedFile $file, Device $device, ?Template $template): Broadcast
    {
        // Validate file size (max 5MB)
        if ($file->getSize() > 5 * 1024 * 1024) {
            throw new \InvalidArgumentException('CSV file size exceeds 5MB limit');
        }

        // Validate file is CSV
        if (! in_array($file->getClientOriginalExtension(), ['csv', 'txt'])) {
            throw new \InvalidArgumentException('File must be a CSV file');
        }

        // Parse CSV and extract phone numbers
        $recipients = $this->parseCsvFile($file);

        if (empty($recipients)) {
            throw new \InvalidArgumentException('CSV file contains no valid phone numbers');
        }

        // Store CSV file
        $csvPath = $file->store('broadcasts', 'local');

        // Create broadcast using recipients
        $broadcast = $this->createBroadcastRecord(
            tenant: $tenant,
            device: $device,
            template: $template,
            recipients: $recipients,
            sourceType: 'csv',
            csvPath: $csvPath
        );

        return $broadcast;
    }

    /**
     * Create a broadcast from an array of recipients.
     *
     * @throws \InvalidArgumentException
     */
    public function createFromRecipients(Tenant $tenant, array $recipients, Device $device, ?Template $template): Broadcast
    {
        if (empty($recipients)) {
            throw new \InvalidArgumentException('Recipients array cannot be empty');
        }

        // Validate and normalize phone numbers
        $validRecipients = [];
        foreach ($recipients as $recipient) {
            try {
                $validRecipients[] = $this->validatePhoneNumber($recipient);
            } catch (\InvalidArgumentException $e) {
                Log::warning('Invalid phone number in broadcast recipients', [
                    'recipient' => $recipient,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (empty($validRecipients)) {
            throw new \InvalidArgumentException('No valid phone numbers found in recipients');
        }

        // Save recipients to a CSV file so they can be retrieved during dispatch
        $csvContent = implode("\n", $validRecipients);
        $csvPath = 'broadcasts/recipients/'.Str::uuid().'.csv';
        Storage::disk('local')->put($csvPath, $csvContent);

        // Create broadcast
        $broadcast = $this->createBroadcastRecord(
            tenant: $tenant,
            device: $device,
            template: $template,
            recipients: $validRecipients,
            sourceType: 'database',
            csvPath: $csvPath,
        );

        return $broadcast;
    }

    /**
     * Dispatch a broadcast by creating message jobs for all recipients.
     *
     * @throws QuotaExceededException
     */
    public function dispatch(Broadcast $broadcast): void
    {
        // Get recipients from broadcast
        $recipients = $this->getRecipientsFromBroadcast($broadcast);

        // Check quota availability
        $remainingQuota = $this->quotaService->getRemainingQuota($broadcast->tenant);
        $isUnlimited = $this->quotaService->isUnlimited($broadcast->tenant);

        if (! $isUnlimited && $remainingQuota < count($recipients)) {
            // Partial dispatch: only send what quota allows
            $recipients = array_slice($recipients, 0, $remainingQuota);

            Log::warning('Broadcast quota exhaustion - partial dispatch', [
                'broadcast_id' => $broadcast->id,
                'total_recipients' => $broadcast->total_recipients,
                'dispatched' => count($recipients),
                'remaining_quota' => $remainingQuota,
            ]);
        }

        if (empty($recipients)) {
            throw new QuotaExceededException($remainingQuota, count($recipients), 'Insufficient quota for broadcast');
        }

        // Update broadcast status
        $broadcast->update([
            'status' => 'running',
            'started_at' => now(),
        ]);

        // Render template once for all recipients, or use manual message
        $message = $broadcast->template
            ? $this->messageService->renderTemplate($broadcast->template, [])
            : ($broadcast->message ?? $broadcast->name);

        // Create message logs and dispatch jobs with random delays
        DB::transaction(function () use ($broadcast, $recipients, $message) {
            foreach ($recipients as $recipient) {
                // Auto-save recipient to contacts (upsert — skip if exists)
                Contact::firstOrCreate(
                    ['tenant_id' => $broadcast->tenant_id, 'phone_number' => $recipient],
                    ['name' => null]
                );

                // Create message log
                $messageLog = MessageLog::create([
                    'tenant_id' => $broadcast->tenant_id,
                    'device_id' => $broadcast->device_id,
                    'broadcast_id' => $broadcast->id,
                    'template_id' => $broadcast->template_id,
                    'recipient' => $recipient,
                    'message' => $message,
                    'status' => 'pending',
                    'source' => 'broadcast',
                    'job_id' => Str::uuid()->toString(),
                ]);

                // Decrement quota
                $this->quotaService->decrement($broadcast->tenant);

                // Dispatch job with random delay (5-10 seconds)
                $delaySeconds = rand(5, 10);
                $this->messageService->dispatchJob($messageLog, $delaySeconds);

                // Update pending count
                $broadcast->increment('pending_count');
            }
        });

        Log::info('Broadcast dispatched', [
            'broadcast_id' => $broadcast->id,
            'total_recipients' => count($recipients),
        ]);
    }

    /**
     * Cancel a broadcast and remove pending jobs.
     */
    public function cancel(Broadcast $broadcast): void
    {
        // Update broadcast status
        $broadcast->update([
            'status' => 'cancelled',
            'completed_at' => now(),
        ]);

        // Cancel all pending message logs
        MessageLog::where('broadcast_id', $broadcast->id)
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);

        Log::info('Broadcast cancelled', [
            'broadcast_id' => $broadcast->id,
        ]);
    }

    /**
     * Get the current progress of a broadcast.
     */
    public function getProgress(Broadcast $broadcast): BroadcastProgress
    {
        // Refresh counts from message logs
        $sent = MessageLog::where('broadcast_id', $broadcast->id)
            ->where('status', 'sent')
            ->count();

        $failed = MessageLog::where('broadcast_id', $broadcast->id)
            ->whereIn('status', ['failed', 'cancelled'])
            ->count();

        $pending = MessageLog::where('broadcast_id', $broadcast->id)
            ->whereIn('status', ['pending', 'retrying'])
            ->count();

        $total = $broadcast->total_recipients;

        // Calculate percentage
        $percentage = $total > 0 ? (($sent + $failed) / $total) * 100 : 0;

        // Update broadcast counts
        $broadcast->update([
            'sent_count' => $sent,
            'failed_count' => $failed,
            'pending_count' => $pending,
        ]);

        // Mark as completed if all messages processed
        if ($pending === 0 && $broadcast->status === 'running') {
            $broadcast->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }

        return new BroadcastProgress(
            total: $total,
            sent: $sent,
            failed: $failed,
            pending: $pending,
            percentage: round($percentage, 2)
        );
    }

    /**
     * Parse CSV file and extract phone numbers.
     *
     * @return array<string>
     */
    protected function parseCsvFile(UploadedFile $file): array
    {
        $recipients = [];
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            throw new \InvalidArgumentException('Unable to read CSV file');
        }

        $headerSkipped = false;

        while (($row = fgetcsv($handle)) !== false) {
            // Skip header row if it looks like a header
            if (! $headerSkipped) {
                $headerSkipped = true;
                // Check if first row contains non-numeric data (likely header)
                if (isset($row[0]) && ! preg_match('/\d/', $row[0])) {
                    continue;
                }
            }

            // Try to find phone number in any column
            foreach ($row as $cell) {
                try {
                    $phoneNumber = $this->validatePhoneNumber($cell);
                    $recipients[] = $phoneNumber;
                    break; // Found valid phone number, move to next row
                } catch (\InvalidArgumentException $e) {
                    // Continue to next column
                    continue;
                }
            }
        }

        fclose($handle);

        // Remove duplicates
        return array_unique($recipients);
    }

    /**
     * Create broadcast record in database.
     *
     * @param  array<string>  $recipients
     */
    protected function createBroadcastRecord(
        Tenant $tenant,
        Device $device,
        ?Template $template,
        array $recipients,
        string $sourceType,
        ?string $csvPath = null
    ): Broadcast {
        return Broadcast::create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'template_id' => $template?->id,
            'name' => 'Broadcast - '.($template?->name ?? 'Manual').' - '.now()->format('Y-m-d H:i'),
            'status' => 'draft',
            'total_recipients' => count($recipients),
            'sent_count' => 0,
            'failed_count' => 0,
            'pending_count' => 0,
            'source_type' => $sourceType,
            'csv_path' => $csvPath,
        ]);
    }

    /**
     * Get recipients from broadcast based on source type.
     *
     * @return array<string>
     */
    protected function getRecipientsFromBroadcast(Broadcast $broadcast): array
    {
        if ($broadcast->csv_path && Storage::disk('local')->exists($broadcast->csv_path)) {
            // Read from stored CSV file (works for both csv and database source types)
            $filePath = Storage::disk('local')->path($broadcast->csv_path);
            $file = new File($filePath);

            return $this->parseCsvFileFromPath($file->getRealPath());
        }

        return [];
    }

    /**
     * Parse CSV file from path.
     *
     * @return array<string>
     */
    protected function parseCsvFileFromPath(string $path): array
    {
        $recipients = [];
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new \InvalidArgumentException('Unable to read CSV file');
        }

        $headerSkipped = false;

        while (($row = fgetcsv($handle)) !== false) {
            if (! $headerSkipped) {
                $headerSkipped = true;
                if (isset($row[0]) && ! preg_match('/\d/', $row[0])) {
                    continue;
                }
            }

            foreach ($row as $cell) {
                try {
                    $phoneNumber = $this->validatePhoneNumber($cell);
                    $recipients[] = $phoneNumber;
                    break;
                } catch (\InvalidArgumentException $e) {
                    continue;
                }
            }
        }

        fclose($handle);

        return array_unique($recipients);
    }

    /**
     * Validate and normalize phone number format.
     *
     * @throws \InvalidArgumentException
     */
    protected function validatePhoneNumber(string $phoneNumber): string
    {
        // Remove any non-digit characters except leading +
        $cleaned = preg_replace('/[^\d+]/', '', trim($phoneNumber));

        // Validate that it contains only digits and optional leading +
        if (! preg_match('/^\+?\d{10,15}$/', $cleaned)) {
            throw new \InvalidArgumentException("Invalid phone number format: {$phoneNumber}");
        }

        // Remove leading + if present for consistency
        return ltrim($cleaned, '+');
    }
}
