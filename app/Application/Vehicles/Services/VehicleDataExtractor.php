<?php

namespace App\Application\Vehicles\Services;

class VehicleDataExtractor
{
    /**
     * @return array{marca: string|null, modelo: string|null, anio: string|null}
     */
    public static function extract(array $responseJson, string $providerCode): array
    {
        $providerCode = strtoupper($providerCode);

        if ($providerCode === 'PLACAS') {
            return self::extractFromPlacas($responseJson);
        }

        if ($providerCode === 'VINDATA') {
            return self::extractFromVinData($responseJson);
        }

        return ['marca' => null, 'modelo' => null, 'anio' => null];
    }

    /**
     * @param array<string, mixed> $data
     * @return array{marca: string|null, modelo: string|null, anio: string|null}
     */
    private static function extractFromPlacas(array $data): array
    {
        $repuve = self::firstListItem($data['repuve'] ?? null);
        $ocra = $data['ocra'] ?? null;
        $ocraVehiculo = is_array($ocra) && is_array($ocra['vehiculo'] ?? null) ? $ocra['vehiculo'] : null;
        $rapi = $data['rapi'] ?? null;
        $rapiVehiculo = is_array($rapi) && is_array($rapi['vehiculo'] ?? null) ? $rapi['vehiculo'] : null;

        $marca = self::firstNonEmpty([
            $repuve['MARCA'] ?? null,
            $ocraVehiculo['marca'] ?? null,
            $rapiVehiculo['marca'] ?? null,
        ]);

        $modelo = self::firstNonEmpty([
            $repuve['MODELO'] ?? null,
            $repuve['Modelo'] ?? null,
            $repuve['modelo'] ?? null,
            $repuve['LINEA'] ?? null,
            $repuve['Linea'] ?? null,
            $repuve['linea'] ?? null,
            $ocraVehiculo['tipoSubmarca'] ?? null,
            $rapiVehiculo['submarca'] ?? null,
        ]);

        $anio = self::firstNonEmpty([
            $repuve['ANIO_MODELO'] ?? null,
            $repuve['ANIO'] ?? null,
            $repuve['anio_modelo'] ?? null,
            $repuve['year'] ?? null,
            $repuve['YEAR'] ?? null,
            self::asYear($ocraVehiculo['modelo'] ?? null),
            self::asYear($rapiVehiculo['modelo'] ?? null),
        ]);

        return [
            'marca' => self::normalizeString($marca),
            'modelo' => self::normalizeString($modelo),
            'anio' => self::normalizeString($anio),
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array{marca: string|null, modelo: string|null, anio: string|null}
     */
    private static function extractFromVinData(array $data): array
    {
        $rawData = $data['rawData'] ?? $data;

        $marca = self::findValue($rawData, ['make', 'Make', 'MAKE', 'marca', 'Marca', 'MARCA']);
        $modelo = self::findValue($rawData, ['model', 'Model', 'MODEL', 'modelo', 'Modelo', 'MODELO']);
        $anio = self::findValue($rawData, ['year', 'Year', 'YEAR', 'anio', 'Anio', 'ANIO', 'año', 'AÑO']);

        return [
            'marca' => self::normalizeString($marca),
            'modelo' => self::normalizeString($modelo),
            'anio' => self::normalizeString($anio),
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, string> $candidates
     */
    private static function findValue(array $data, array $candidates): mixed
    {
        foreach ($candidates as $key) {
            $value = self::recursiveSearch($data, $key);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function recursiveSearch(array $data, string $key): mixed
    {
        foreach ($data as $k => $v) {
            if ((string) $k === $key) {
                return is_scalar($v) ? $v : null;
            }

            if (is_array($v)) {
                $found = self::recursiveSearch($v, $key);
                if ($found !== null && $found !== '') {
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * @param mixed $section
     */
    private static function firstListItem(mixed $section): ?array
    {
        if (!is_array($section)) {
            return null;
        }

        if (isset($section[0]) && is_array($section[0])) {
            return $section[0];
        }

        if (count($section) > 0 && array_keys($section) === range(0, count($section) - 1)) {
            return reset($section);
        }

        return null;
    }

    /**
     * @param array<int, mixed> $values
     */
    private static function firstNonEmpty(array $values): mixed
    {
        foreach ($values as $value) {
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private static function asYear(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $str = trim((string) $value);
        if (!preg_match('/^\d{4}$/', $str) || $str < '1900' || $str > '2100') {
            return null;
        }

        return (int) $str;
    }

    private static function normalizeString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $str = trim((string) $value);

        return $str === '' ? null : $str;
    }
}
