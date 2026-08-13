<?php

namespace App\Domain\NotificationCases\ValueObjects;

use InvalidArgumentException;

final class CaseNumber
{
    private function __construct(private readonly string $value) {}

    public static function fromSequence(int $year, int $sequence): self
    {
        if ($year < 2000 || $year > 9999 || $sequence < 1 || $sequence > 999999) {
            throw new InvalidArgumentException('Secuencia anual de folio fuera de rango.');
        }

        return new self(sprintf('NT-%04d-%06d', $year, $sequence));
    }

    public function value(): string
    {
        return $this->value;
    }
}
