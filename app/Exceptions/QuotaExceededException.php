<?php

namespace App\Exceptions;

use Exception;

class QuotaExceededException extends Exception
{
    public function __construct(
        public readonly int $remaining,
        public readonly int $required,
        string $message = 'Quota exceeded',
    ) {
        parent::__construct($message);
    }
}
