<?php

namespace App\Domain\Credits\Entities;

use App\Domain\Credits\ValueObjects\Amount;
use App\Domain\Credits\ValueObjects\CorrelationId;
use DateTimeImmutable;

class LedgerEntry
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        private readonly ?int $id,
        private readonly int $walletId,
        private readonly Amount $delta,
        private readonly string $reason,
        private readonly array $meta,
        private readonly CorrelationId $correlationId,
        private readonly DateTimeImmutable $createdAt
    ) {
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function walletId(): int
    {
        return $this->walletId;
    }

    public function delta(): Amount
    {
        return $this->delta;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function meta(): array
    {
        return $this->meta;
    }

    public function correlationId(): CorrelationId
    {
        return $this->correlationId;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
