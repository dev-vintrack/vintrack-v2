<?php

namespace App\Domain\NotificationCases\ValueObjects;

use InvalidArgumentException;

final class Vin
{
    private function __construct(private readonly string $value) {}

    public static function fromString(string $value): self
    {
        $normalized = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', trim($value)) ?? '');
        if (! preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', $normalized)) {
            throw new InvalidArgumentException('VIN inválido: debe contener 17 caracteres y excluir I, O y Q.');
        }

        return new self($normalized);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
