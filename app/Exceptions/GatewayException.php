<?php

namespace App\Exceptions;

use Exception;

class GatewayException extends Exception
{
    public function __construct(
        string $message = 'Gateway error occurred',
        public readonly ?string $gatewayError = null,
        int $code = 0,
    ) {
        parent::__construct($message, $code);
    }
}
