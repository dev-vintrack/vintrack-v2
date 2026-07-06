<?php

namespace App\Domain\Providers\Repositories;

use App\Domain\Providers\Entities\Provider;
use App\Domain\Providers\ValueObjects\ProviderCode;
use App\Domain\Providers\ValueObjects\ProviderId;

interface ProviderRepositoryInterface
{
    public function findById(ProviderId $id): ?Provider;

    public function findByCode(ProviderCode $code): ?Provider;

    public function findByCodeOrFail(ProviderCode $code): Provider;

    /**
     * @return Provider[]
     */
    public function findEnabled(): array;
}
