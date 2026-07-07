<?php

namespace App\Domain\Credits\ValueObjects;

final class Amount
{
    private function __construct(
        private readonly float $value
    ) {
    }

    public static function fromFloat(float $value): self
    {
        return new self($value);
    }

    public function value(): float
    {
        return $this->value;
    }

    public function add(self $other): self
    {
        return new self($this->value + $other->value);
    }

    public function isNegative(): bool
    {
        return $this->value < 0;
    }

    public function absolute(): Money
    {
        return Money::fromFloat(abs($this->value));
    }
}
