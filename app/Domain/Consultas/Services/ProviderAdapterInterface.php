<?php

namespace App\Domain\Consultas\Services;

use App\Domain\Consultas\ValueObjects\ConsultationRequest;
use App\Domain\Consultas\ValueObjects\ConsultationResponse;

interface ProviderAdapterInterface
{
    public function consult(ConsultationRequest $request): ConsultationResponse;

    public function supports(string $providerCode): bool;
}
