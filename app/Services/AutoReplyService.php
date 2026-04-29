<?php

namespace App\Services;

use App\Jobs\SendMessageJob;
use App\Models\AutoReplyCooldown;
use App\Models\AutoReplyLog;
use App\Models\Device;
use App\Models\KeywordRule;
use App\Models\MessageLog;
use App\Services\Contracts\AutoReplyServiceInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AutoReplyService implements AutoReplyServiceInterface
{
    /**
     * Process an incoming message from webhook.
     *
     * Finds the device by gateway_device_id, matches keywords,
     * checks cooldown, dispatches reply, and logs everything.
     */
    public function processIncomingMessage(string $deviceId, string $from, string $message): void
    {
        $device = Device::where('gateway_device_id', $deviceId)->first();

        if (! $device) {
            Log::warning('Device not found for auto-reply processing', [
                'gateway_device_id' => $deviceId,
            ]);

            return;
        }

        // Clean up expired cooldowns for this device
        $this->cleanupExpiredCooldowns($device->id);

        $keywordRule = $this->matchKeyword($device, $message);
        $matched = $keywordRule !== null;
        $replySent = false;

        // Log incoming message to AutoReplyLog
        $autoReplyLog = AutoReplyLog::create([
            'tenant_id' => $device->tenant_id,
            'device_id' => $device->id,
            'keyword_rule_id' => $keywordRule?->id,
            'from' => $from,
            'message' => $message,
            'matched' => $matched,
            'reply_sent' => false,
            'received_at' => now(),
        ]);

        if (! $keywordRule) {
            return;
        }

        // Check cooldown using the public interface (gateway_device_id)
        if (! $this->canReply($deviceId, $from, $keywordRule->keyword)) {
            Log::info('Auto-reply skipped due to cooldown', [
                'device_id' => $device->id,
                'from' => $from,
                'keyword' => $keywordRule->keyword,
            ]);

            return;
        }

        // Create MessageLog and dispatch SendMessageJob for the reply
        $messageLog = MessageLog::create([
            'tenant_id' => $device->tenant_id,
            'device_id' => $device->id,
            'recipient' => $from,
            'message' => $keywordRule->reply,
            'status' => 'pending',
            'source' => 'auto_reply',
            'job_id' => (string) Str::uuid(),
        ]);

        SendMessageJob::dispatch($messageLog);

        // Set cooldown to prevent reply loop (1 reply per sender per keyword per 60 minutes)
        $this->setCooldown($device->id, $keywordRule->id, $from);

        $autoReplyLog->update(['reply_sent' => true]);

        Log::info('Auto-reply sent', [
            'device_id' => $device->id,
            'from' => $from,
            'keyword' => $keywordRule->keyword,
            'message_log_id' => $messageLog->id,
        ]);
    }

    /**
     * Match a message against active keyword rules for a device.
     *
     * Returns the highest priority matching rule, or null if no match.
     * Matching is case-insensitive using mb_strtolower for UTF-8 safety.
     */
    public function matchKeyword(Device $device, string $message): ?KeywordRule
    {
        $keywordRules = KeywordRule::where('device_id', $device->id)
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->get();

        $messageLower = mb_strtolower($message);

        foreach ($keywordRules as $rule) {
            if (str_contains($messageLower, mb_strtolower($rule->keyword))) {
                return $rule;
            }
        }

        return null;
    }

    /**
     * Check if a reply can be sent (cooldown check).
     *
     * Returns false if there is an active (non-expired) cooldown
     * for the given device, sender, and keyword combination.
     */
    public function canReply(string $deviceId, string $from, string $keyword): bool
    {
        $device = Device::where('gateway_device_id', $deviceId)->first();

        if (! $device) {
            return false;
        }

        $keywordRule = KeywordRule::where('device_id', $device->id)
            ->where('keyword', $keyword)
            ->first();

        if (! $keywordRule) {
            return false;
        }

        return ! AutoReplyCooldown::where('device_id', $device->id)
            ->where('keyword_rule_id', $keywordRule->id)
            ->where('from', $from)
            ->where('expires_at', '>', now())
            ->exists();
    }

    /**
     * Set cooldown for auto-reply (1 reply per sender per keyword per configured minutes).
     */
    protected function setCooldown(int $deviceId, int $keywordRuleId, string $from): void
    {
        $cooldownMinutes = (int) config('wa-automation.auto_reply.cooldown_minutes', 60);

        AutoReplyCooldown::create([
            'device_id' => $deviceId,
            'keyword_rule_id' => $keywordRuleId,
            'from' => $from,
            'expires_at' => now()->addMinutes($cooldownMinutes),
        ]);
    }

    /**
     * Clean up expired cooldowns for a device to prevent table bloat.
     */
    protected function cleanupExpiredCooldowns(int $deviceId): void
    {
        AutoReplyCooldown::where('device_id', $deviceId)
            ->where('expires_at', '<=', now())
            ->delete();
    }
}
