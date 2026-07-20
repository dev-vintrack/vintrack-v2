<?php

namespace App\Infrastructure\Persistence\Eloquent\Credits;

use App\Domain\Credits\Entities\LedgerEntry;
use App\Domain\Credits\Repositories\LedgerRepositoryInterface;
use App\Domain\Credits\ValueObjects\Amount;
use App\Domain\Credits\ValueObjects\CorrelationId;
use App\Infrastructure\Persistence\Models\WalletLedgerEntry as LedgerModel;
use DateTimeImmutable;

class LedgerRepository implements LedgerRepositoryInterface
{
    public function save(LedgerEntry $entry): void
    {
        LedgerModel::create([
            'wallet_id' => $entry->walletId(),
            'provider_service_id' => $entry->providerServiceId(),
            'delta' => $entry->delta()->value(),
            'reason' => $entry->reason(),
            'meta' => $entry->meta(),
            'correlation_id' => $entry->correlationId()->value(),
            'created_at' => $entry->createdAt()->format('Y-m-d H:i:s'),
        ]);
    }

    public function findByCorrelationId(CorrelationId $correlationId): ?LedgerEntry
    {
        $model = LedgerModel::where('correlation_id', $correlationId->value())->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function findByWalletId(int $walletId, int $limit = 50): array
    {
        return LedgerModel::where('wallet_id', $walletId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn (LedgerModel $model) => $this->toEntity($model))
            ->all();
    }

    private function toEntity(LedgerModel $model): LedgerEntry
    {
        return new LedgerEntry(
            $model->id,
            $model->wallet_id,
            $model->provider_service_id,
            Amount::fromFloat((float) $model->delta),
            $model->reason,
            $model->meta ?? [],
            CorrelationId::fromString($model->correlation_id),
            new DateTimeImmutable($model->created_at)
        );
    }
}
