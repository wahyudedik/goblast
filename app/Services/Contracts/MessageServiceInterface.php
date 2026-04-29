<?php

namespace App\Services\Contracts;

use App\Exceptions\QuotaExceededException;
use App\Models\Device;
use App\Models\MessageLog;
use App\Models\Template;

interface MessageServiceInterface
{
    /**
     * Send a single message to a recipient.
     *
     * @param  Device  $device  The device to send from
     * @param  string  $to  The recipient phone number
     * @param  string  $message  The message content
     * @param  Template|null  $template  Optional template for rendering
     * @return MessageLog The created message log entry
     *
     * @throws QuotaExceededException
     */
    public function sendSingle(Device $device, string $to, string $message, ?Template $template = null): MessageLog;

    /**
     * Render a template with variable substitution.
     *
     * @param  Template  $template  The template to render
     * @param  array  $context  The context data for variable substitution
     * @return string The rendered message content
     */
    public function renderTemplate(Template $template, array $context): string;

    /**
     * Dispatch a message job to the queue with optional delay.
     *
     * @param  MessageLog  $log  The message log entry
     * @param  int  $delaySeconds  Optional delay in seconds before processing
     */
    public function dispatchJob(MessageLog $log, int $delaySeconds = 0): void;
}
