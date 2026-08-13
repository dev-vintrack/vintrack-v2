<?php

namespace App\Application\NotificationCases\Services;

use App\Domain\NotificationCases\Enums\CanonicalCriterion;
use App\Domain\NotificationCases\ValueObjects\Vin;
use InvalidArgumentException;

final class ConsultationVinMapper
{
    public const VIN_NOT_AVAILABLE = 'VIN_NOT_AVAILABLE';

    /** @param array<string, mixed> $response */
    public function map(string $criterion, string $value, string $adapterCode, array $response): Vin|string
    {
        if (CanonicalCriterion::fromConsultation($criterion) === CanonicalCriterion::VIN) {
            return Vin::fromString($value);
        }

        // No verified Placas fixture/path exists in the repository. Contractually, a
        // plate response must not be searched heuristically because ambiguous keys can
        // assign a different vehicle's VIN. A future mapper may add only evidenced paths.
        if (strtolower($adapterCode) === 'placas') {
            return self::VIN_NOT_AVAILABLE;
        }

        throw new InvalidArgumentException('No existe mapper verificable para el servicio y criterio indicados.');
    }
}
