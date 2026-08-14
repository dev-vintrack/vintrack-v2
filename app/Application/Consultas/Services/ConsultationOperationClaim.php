<?php

namespace App\Application\Consultas\Services;

final class ConsultationOperationClaim
{
    public function __construct(
        public readonly int $id,
        public readonly bool $replay,
        public readonly ?int $consultationId = null,
        public readonly ?array $responseSnapshot = null,
    ) {}
}
