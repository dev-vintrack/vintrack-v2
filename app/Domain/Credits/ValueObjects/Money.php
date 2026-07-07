<?php

namespace App\Domain\Credits\ValueObjects;

use InvalidArgumentException;

final class Money
{
    private function __construct(
        private readonly float $amount
    ) {
        if ($amount < 0) {
            throw new InvalidArgumentException('Money amount cannot be negative.');
        }
    }

    public static function fromFloat(float $amount): self
    {
        return new self($amount);
    }

    public function amount(): float
    {
        return $this->amount;
    }

    public function add(self $other): self
    {
        return new self($this->amount + $other->amount);
    }

    public function subtract(self $other): self
    {
        if ($other->amount > $this->amount) {
            throw new InvalidArgumentException('Insufficient amount for subtraction.');
        }

        return new self($this->amount - $other->amount);
    }

    public function isGreaterThanOrEqual(self $other): bool
    {
        return $this->amount >= $other->amount;
    }

    public function equals(self $other): bool
    {
        return abs($this->amount - $other->amount) < 0.0001;
    }
}
