<?php

namespace App\Services;

use App\Exceptions\QuotaExceededException;
use App\Jobs\SendMessageJob;
use App\Models\Device;
use App\Models\MessageLog;
use App\Models\Template;
use App\Services\Contracts\MessageServiceInterface;
use Illuminate\Support\Str;

class MessageService implements MessageServiceInterface
{
    public function __construct(
        protected QuotaService $quotaService,
    ) {}

    /**
     * Send a single message to a recipient.
     *
     * @throws QuotaExceededException
     */
    public function sendSingle(Device $device, string $to, string $message, ?Template $template = null): MessageLog
    {
        // Validate phone number format
        $to = $this->validatePhoneNumber($to);

        // Check quota availability
        if ($this->quotaService->isExhausted($device->tenant)) {
            $remaining = $this->quotaService->getRemainingQuota($device->tenant);
            throw new QuotaExceededException($remaining, 1, 'Quota exhausted for this tenant');
        }

        // Render message if template is provided
        if ($template) {
            $message = $this->renderTemplate($template, []);
        }

        // Create message log entry
        $messageLog = MessageLog::create([
            'tenant_id' => $device->tenant_id,
            'device_id' => $device->id,
            'template_id' => $template?->id,
            'recipient' => $to,
            'message' => $message,
            'status' => 'pending',
            'source' => 'api',
            'job_id' => Str::uuid()->toString(),
        ]);

        // Decrement quota
        $this->quotaService->decrement($device->tenant);

        // Dispatch job to queue
        $this->dispatchJob($messageLog);

        return $messageLog;
    }

    /**
     * Render a template with variable substitution.
     */
    public function renderTemplate(Template $template, array $context): string
    {
        $content = $template->content;

        // Extract variables from template (format: {variable_name})
        if (preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', $content, $matches)) {
            foreach ($matches[1] as $variable) {
                $value = $context[$variable] ?? '';

                // Log warning if variable is missing
                if (! isset($context[$variable])) {
                    \Log::warning('Template variable missing', [
                        'template_id' => $template->id,
                        'variable' => $variable,
                    ]);
                }

                // Replace variable with value (empty string if not found)
                $content = str_replace("{{$variable}}", (string) $value, $content);
            }
        }

        return $content;
    }

    /**
     * Dispatch a message job to the queue with optional delay.
     */
    public function dispatchJob(MessageLog $log, int $delaySeconds = 0): void
    {
        if ($delaySeconds > 0) {
            SendMessageJob::dispatch($log->withoutRelations())->delay(now()->addSeconds($delaySeconds));
        } else {
            SendMessageJob::dispatch($log->withoutRelations());
        }
    }

    /**
     * Validate and normalize phone number format.
     *
     * @throws \InvalidArgumentException
     */
    protected function validatePhoneNumber(string $to): string
    {
        // Remove any non-digit characters except leading +
        $cleaned = preg_replace('/[^\d+]/', '', $to);

        // Validate that it contains only digits and optional leading +
        if (! preg_match('/^\+?\d{10,15}$/', $cleaned)) {
            throw new \InvalidArgumentException("Invalid phone number format: {$to}");
        }

        // Remove leading + if present for consistency
        return ltrim($cleaned, '+');
    }
}
