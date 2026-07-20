<?php

namespace App\Infrastructure\Persistence\Eloquent\Providers;

use App\Domain\Credits\ValueObjects\Money;
use App\Domain\Providers\Entities\ProviderService;
use App\Domain\Providers\Repositories\ProviderServiceRepositoryInterface;
use App\Infrastructure\Persistence\Models\ProviderService as ProviderServiceModel;

class ProviderServiceRepository implements ProviderServiceRepositoryInterface
{
    public function findById(int $id): ?ProviderService
    {
        $model = ProviderServiceModel::find($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function findByProviderId(int $providerId): array
    {
        return ProviderServiceModel::where('provider_id', $providerId)
            ->get()
            ->map(fn (ProviderServiceModel $model) => $this->toEntity($model))
            ->all();
    }

    public function findEnabledByProviderId(int $providerId): array
    {
        return ProviderServiceModel::where('provider_id', $providerId)
            ->where('enabled', true)
            ->get()
            ->map(fn (ProviderServiceModel $model) => $this->toEntity($model))
            ->all();
    }

    public function findByProviderIdAndKey(int $providerId, string $key): ?ProviderService
    {
        $model = ProviderServiceModel::where('provider_id', $providerId)
            ->where('key', $key)
            ->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function save(ProviderService $service): void
    {
        ProviderServiceModel::updateOrCreate(
            ['id' => $service->id()],
            [
                'provider_id' => $service->providerId(),
                'key' => $service->key(),
                'name' => $service->name(),
                'credit_cost' => $service->creditCost()->amount(),
                'enabled' => $service->isEnabled(),
            ]
        );
    }

    private function toEntity(ProviderServiceModel $model): ProviderService
    {
        return new ProviderService(
            $model->id,
            $model->provider_id,
            $model->key,
            $model->name,
            $model->enabled,
            Money::fromFloat((float) $model->credit_cost)
        );
    }
}
