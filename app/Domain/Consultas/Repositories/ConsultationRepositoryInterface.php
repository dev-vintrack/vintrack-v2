<?php

namespace App\Domain\Consultas\Repositories;

use App\Domain\Consultas\Entities\Consultation;

interface ConsultationRepositoryInterface
{
    public function save(Consultation $consultation): void;

    public function findById(int $id): ?Consultation;

    /**
     * @return Consultation[]
     */
    public function findByUserId(int $userId, int $limit = 50): array;
}
