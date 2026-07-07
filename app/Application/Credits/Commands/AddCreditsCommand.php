<?php

namespace App\Application\Credits\Commands;

class AddCreditsCommand
{
    public function __construct(
        public readonly int $userId,
        public readonly int $providerId,
        public readonly float $amount,
        public readonly string $reason,
        public readonly string $correlationId,
        public readonly ?int $adminId = null,
        public readonly ?\DateTimeImmutable $validityEnd = null
    ) {
    }
}
