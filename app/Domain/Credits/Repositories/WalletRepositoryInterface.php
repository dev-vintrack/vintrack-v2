<?php

namespace App\Domain\Credits\Repositories;

use App\Domain\Credits\Entities\Wallet;
use App\Domain\Credits\ValueObjects\WalletId;

interface WalletRepositoryInterface
{
    public function findById(WalletId $id): ?Wallet;

    public function findByUserAndProvider(int $userId, int $providerId): ?Wallet;

    public function findByUserAndProviderOrCreate(int $userId, int $providerId): Wallet;

    /**
     * @return Wallet[]
     */
    public function findByUser(int $userId): array;

    public function save(Wallet $wallet): void;
}
