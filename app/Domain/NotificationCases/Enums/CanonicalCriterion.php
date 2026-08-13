<?php

namespace App\Domain\NotificationCases\Enums;

use InvalidArgumentException;

enum CanonicalCriterion: string
{
    case VIN = 'VIN';
    case PLATE = 'PLATE';

    public static function fromConsultation(string $criterion): self
    {
        return match (strtolower(trim($criterion))) {
            'niv', 'vin' => self::VIN,
            'placa' => self::PLATE,
            default => throw new InvalidArgumentException('Criterio de consulta no soportado.'),
        };
    }
}
