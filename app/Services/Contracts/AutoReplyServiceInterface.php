<?php

namespace App\Services\Contracts;

use App\Models\Device;
use App\Models\KeywordRule;

interface AutoReplyServiceInterface
{
    /**
     * Process an incoming message from webhook.
     */
    public function processIncomingMessage(string $deviceId, string $from, string $message): void;

    /**
     * Match a message against keyword rules for a device.
     */
    public function matchKeyword(Device $device, string $message): ?KeywordRule;

    /**
     * Check if a reply can be sent (cooldown check).
     */
    public function canReply(string $deviceId, string $from, string $keyword): bool;
}
