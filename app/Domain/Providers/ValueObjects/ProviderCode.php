<?php

namespace App\Domain\Providers\ValueObjects;

use InvalidArgumentException;

final class ProviderCode
{
    private function __construct(
        private readonly string $value
    ) {
        if (preg_match('/[^a-zA-Z0-9_-]/', $value)) {
            throw new InvalidArgumentException('Provider code must be alphanumeric, dashes and underscores allowed.');
        }

        if (strlen($value) < 2 || strlen($value) > 32) {
            throw new InvalidArgumentException('Provider code must be between 2 and 32 characters.');
        }
    }

    public static function fromString(string $code): self
    {
        return new self(strtoupper(trim($code)));
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
