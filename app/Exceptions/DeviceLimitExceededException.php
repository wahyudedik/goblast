<?php

namespace App\Exceptions;

use Exception;

class DeviceLimitExceededException extends Exception
{
    public function __construct(
        public readonly int $currentCount,
        public readonly int $maxAllowed,
        string $message = '',
    ) {
        if (! $message) {
            $message = "Device limit exceeded. Current: {$currentCount}, Max allowed: {$maxAllowed}";
        }

        parent::__construct($message);
    }
}
