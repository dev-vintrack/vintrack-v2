<?php

namespace App\Infrastructure\Persistence\Eloquent\Providers;

use App\Domain\Providers\Entities\Provider as ProviderEntity;
use App\Domain\Providers\Repositories\ProviderRepositoryInterface;
use App\Domain\Providers\ValueObjects\ProviderCode;
use App\Domain\Providers\ValueObjects\ProviderId;
use App\Infrastructure\Persistence\Models\Provider as ProviderModel;
use RuntimeException;

class ProviderRepository implements ProviderRepositoryInterface
{
    public function findById(ProviderId $id): ?ProviderEntity
    {
        $model = ProviderModel::find($id->value());

        return $model ? $this->toEntity($model) : null;
    }

    public function findByCode(ProviderCode $code): ?ProviderEntity
    {
        $model = ProviderModel::where('code', $code->value())->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function findByAdapterCode(string $adapterCode): ?ProviderEntity
    {
        $model = ProviderModel::where('adapter_code', $adapterCode)->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function findByCodeOrFail(ProviderCode $code): ProviderEntity
    {
        $entity = $this->findByCode($code);

        if ($entity === null) {
            throw new RuntimeException("Provider with code [{$code->value()}] not found.");
        }

        return $entity;
    }

    public function findEnabled(): array
    {
        return ProviderModel::where('enabled', true)
            ->get()
            ->map(fn (ProviderModel $model) => $this->toEntity($model))
            ->all();
    }

    private function toEntity(ProviderModel $model): ProviderEntity
    {
        return new ProviderEntity(
            ProviderId::fromInt($model->id),
            ProviderCode::fromString($model->code),
            $model->adapter_code,
            $model->name,
            $model->base_url,
            $model->policies_json ?? [],
            $model->enabled
        );
    }
}
