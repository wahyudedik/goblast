<?php

namespace App\Services\ValueObjects;

readonly class BroadcastProgress
{
    public function __construct(
        public int $total,
        public int $sent,
        public int $failed,
        public int $pending,
        public float $percentage,
    ) {}
}
