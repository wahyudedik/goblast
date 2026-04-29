<?php

namespace App\Services\Contracts;

use App\Exceptions\QuotaExceededException;
use App\Models\Broadcast;
use App\Models\Device;
use App\Models\Template;
use App\Models\Tenant;
use App\Services\ValueObjects\BroadcastProgress;
use Illuminate\Http\UploadedFile;

interface BroadcastServiceInterface
{
    /**
     * Create a broadcast from a CSV file.
     *
     * @param  Tenant  $tenant  The tenant creating the broadcast
     * @param  UploadedFile  $file  The CSV file containing recipients
     * @param  Device  $device  The device to send from
     * @param  Template  $template  The template to use
     * @return Broadcast The created broadcast
     *
     * @throws \InvalidArgumentException If CSV is invalid
     */
    public function createFromCsv(Tenant $tenant, UploadedFile $file, Device $device, ?Template $template): Broadcast;

    /**
     * Create a broadcast from an array of recipients.
     *
     * @param  Tenant  $tenant  The tenant creating the broadcast
     * @param  array<string>  $recipients  Array of phone numbers
     * @param  Device  $device  The device to send from
     * @param  Template  $template  The template to use
     * @return Broadcast The created broadcast
     *
     * @throws \InvalidArgumentException If recipients are invalid
     */
    public function createFromRecipients(Tenant $tenant, array $recipients, Device $device, ?Template $template): Broadcast;

    /**
     * Dispatch a broadcast by creating message jobs for all recipients.
     *
     * @param  Broadcast  $broadcast  The broadcast to dispatch
     *
     * @throws QuotaExceededException If quota is insufficient
     */
    public function dispatch(Broadcast $broadcast): void;

    /**
     * Cancel a broadcast and remove pending jobs.
     *
     * @param  Broadcast  $broadcast  The broadcast to cancel
     */
    public function cancel(Broadcast $broadcast): void;

    /**
     * Get the current progress of a broadcast.
     *
     * @param  Broadcast  $broadcast  The broadcast to check
     * @return BroadcastProgress The progress information
     */
    public function getProgress(Broadcast $broadcast): BroadcastProgress;
}
