<?php

namespace App\Domain\Providers\Repositories;

use App\Domain\Providers\Entities\ProviderService;

interface ProviderServiceRepositoryInterface
{
    public function findById(int $id): ?ProviderService;

    /**
     * @return ProviderService[]
     */
    public function findByProviderId(int $providerId): array;

    /**
     * @return ProviderService[]
     */
    public function findEnabledByProviderId(int $providerId): array;

    public function findByProviderIdAndKey(int $providerId, string $key): ?ProviderService;

    public function save(ProviderService $service): void;
}
