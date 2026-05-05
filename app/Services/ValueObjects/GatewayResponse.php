<?php

namespace App\Services\ValueObjects;

readonly class GatewayResponse
{
    public function __construct(
        public bool $success,
        public string $status,
        public ?string $messageId = null,
        public ?string $errorMessage = null,
    ) {}
}
