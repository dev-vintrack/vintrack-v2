<?php

namespace App\Domain\Credits\Repositories;

use App\Domain\Credits\Entities\Wallet;
use App\Domain\Credits\ValueObjects\WalletId;

interface WalletRepositoryInterface
{
    public function findById(WalletId $id): ?Wallet;

    public function findByUserAndService(int $userId, int $providerServiceId): ?Wallet;

    public function findByUserAndServiceOrCreate(int $userId, int $providerServiceId): Wallet;

    /**
     * @return Wallet[]
     */
    public function findByUser(int $userId): array;

    public function save(Wallet $wallet): void;
}
