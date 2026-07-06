<?php

namespace App\Domain\Providers\ValueObjects;

use InvalidArgumentException;

final class ProviderId
{
    private function __construct(
        private readonly int $value
    ) {
        if ($value <= 0) {
            throw new InvalidArgumentException('Provider id must be a positive integer.');
        }
    }

    public static function fromInt(int $id): self
    {
        return new self($id);
    }

    public function value(): int
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
