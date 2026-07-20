<?php

namespace App\Application\Credits\Commands;

class DebitCreditsCommand
{
    public function __construct(
        public readonly int $userId,
        public readonly int $providerServiceId,
        public readonly float $amount,
        public readonly string $reason,
        public readonly string $correlationId,
        public readonly ?int $consultationId = null
    ) {
    }
}
