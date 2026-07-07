<?php

namespace App\Domain\Credits\Repositories;

use App\Domain\Credits\Entities\LedgerEntry;
use App\Domain\Credits\ValueObjects\CorrelationId;

interface LedgerRepositoryInterface
{
    public function save(LedgerEntry $entry): void;

    public function findByCorrelationId(CorrelationId $correlationId): ?LedgerEntry;

    /**
     * @return LedgerEntry[]
     */
    public function findByWalletId(int $walletId, int $limit = 50): array;
}
