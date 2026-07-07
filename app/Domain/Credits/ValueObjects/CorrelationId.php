<?php

namespace App\Domain\Credits\ValueObjects;

use InvalidArgumentException;

final class CorrelationId
{
    private function __construct(
        private readonly string $value
    ) {
        if (trim($value) === '') {
            throw new InvalidArgumentException('Correlation id cannot be empty.');
        }
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
