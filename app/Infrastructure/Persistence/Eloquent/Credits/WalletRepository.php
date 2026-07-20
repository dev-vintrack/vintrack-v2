<?php

namespace App\Infrastructure\Persistence\Eloquent\Credits;

use App\Domain\Credits\Entities\Wallet;
use App\Domain\Credits\Repositories\WalletRepositoryInterface;
use App\Domain\Credits\ValueObjects\Money;
use App\Domain\Credits\ValueObjects\WalletId;
use App\Infrastructure\Persistence\Models\UserProviderWallet as WalletModel;
use DateTimeImmutable;

class WalletRepository implements WalletRepositoryInterface
{
    public function findById(WalletId $id): ?Wallet
    {
        $model = WalletModel::find($id->value());

        return $model ? $this->toEntity($model) : null;
    }

    public function findByUserAndService(int $userId, int $providerServiceId): ?Wallet
    {
        $model = WalletModel::where('user_id', $userId)
            ->where('provider_service_id', $providerServiceId)
            ->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function findByUserAndServiceOrCreate(int $userId, int $providerServiceId): Wallet
    {
        $wallet = $this->findByUserAndService($userId, $providerServiceId);
        if ($wallet) {
            return $wallet;
        }

        $service = \App\Infrastructure\Persistence\Models\ProviderService::find($providerServiceId);

        $model = WalletModel::create([
            'user_id' => $userId,
            'provider_id' => $service?->provider_id,
            'provider_service_id' => $providerServiceId,
            'balance' => 0,
            'min_alert' => 5,
            'validity_start' => null,
            'validity_end' => null,
        ]);

        return $this->toEntity($model);
    }

    public function findByUser(int $userId): array
    {
        return WalletModel::where('user_id', $userId)
            ->get()
            ->map(fn (WalletModel $model) => $this->toEntity($model))
            ->all();
    }

    public function save(Wallet $wallet): void
    {
        $data = [
            'user_id' => $wallet->userId(),
            'provider_service_id' => $wallet->providerServiceId(),
            'balance' => $wallet->balance()->amount(),
            'min_alert' => $wallet->minAlert()->amount(),
            'validity_start' => $wallet->validityStart()?->format('Y-m-d H:i:s'),
            'validity_end' => $wallet->validityEnd()?->format('Y-m-d H:i:s'),
        ];

        if ($wallet->id() !== null) {
            WalletModel::where('id', $wallet->id()->value())->update($data);
        } else {
            WalletModel::create($data);
        }
    }

    private function toEntity(WalletModel $model): Wallet
    {
        return new Wallet(
            WalletId::fromInt($model->id),
            $model->user_id,
            $model->provider_service_id,
            Money::fromFloat((float) $model->balance),
            Money::fromFloat((float) $model->min_alert),
            $model->validity_start ? new DateTimeImmutable($model->validity_start) : null,
            $model->validity_end ? new DateTimeImmutable($model->validity_end) : null
        );
    }
}
