<?php

namespace App\Domain\Credits\Entities;

use App\Domain\Credits\ValueObjects\Money;
use App\Domain\Credits\ValueObjects\WalletId;
use DateTimeImmutable;
use InvalidArgumentException;

class Wallet
{
    public function __construct(
        private readonly ?WalletId $id,
        private readonly int $userId,
        private readonly int $providerServiceId,
        private Money $balance,
        private Money $minAlert,
        private ?DateTimeImmutable $validityStart,
        private ?DateTimeImmutable $validityEnd
    ) {
    }

    public function id(): ?WalletId
    {
        return $this->id;
    }

    public function userId(): int
    {
        return $this->userId;
    }

    public function providerServiceId(): int
    {
        return $this->providerServiceId;
    }

    public function balance(): Money
    {
        return $this->balance;
    }

    public function minAlert(): Money
    {
        return $this->minAlert;
    }

    public function validityStart(): ?DateTimeImmutable
    {
        return $this->validityStart;
    }

    public function validityEnd(): ?DateTimeImmutable
    {
        return $this->validityEnd;
    }

    public function setValidityStart(?DateTimeImmutable $validityStart): void
    {
        $this->validityStart = $validityStart;
    }

    public function setValidityEnd(?DateTimeImmutable $validityEnd): void
    {
        $this->validityEnd = $validityEnd;
    }

    public function credit(Money $amount): void
    {
        $this->balance = $this->balance->add($amount);
    }

    public function debit(Money $amount): void
    {
        if (!$this->balance->isGreaterThanOrEqual($amount)) {
            throw new InvalidArgumentException('Insufficient balance for debit.');
        }

        $this->balance = $this->balance->subtract($amount);
    }

    public function hasLowBalance(): bool
    {
        return !$this->balance->isGreaterThanOrEqual($this->minAlert);
    }

    public function isValidAt(DateTimeImmutable $date): bool
    {
        if ($this->validityStart !== null && $date < $this->validityStart) {
            return false;
        }

        if ($this->validityEnd !== null && $date > $this->validityEnd) {
            return false;
        }

        return true;
    }
}
