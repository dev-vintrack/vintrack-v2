<?php

namespace App\Application\Consultas\Services;

use App\Domain\Consultas\Entities\Consultation;
use App\Domain\Consultas\ValueObjects\ConsultationResponse;

class ConsultationResult
{
    public function __construct(
        private readonly ConsultationResponse $response,
        private readonly Consultation $consultation
    ) {
    }

    public function response(): ConsultationResponse
    {
        return $this->response;
    }

    public function consultation(): Consultation
    {
        return $this->consultation;
    }
}
