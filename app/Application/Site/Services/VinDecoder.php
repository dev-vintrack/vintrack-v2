<?php

namespace App\Application\Site\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use Throwable;

class VinDecoder
{
    private const BASE_URL = 'https://vpic.nhtsa.dot.gov/api/vehicles/decodevinvalues';

    public function decode(string $vin, ?int $modelYear = null): ?array
    {
        $vin = $this->sanitize($vin);

        if ($vin === '') {
            return null;
        }

        try {
            $url = self::BASE_URL . '/' . urlencode($vin) . '?format=json';

            if ($modelYear) {
                $url .= '&modelyear=' . $modelYear;
            }

            $response = Http::timeout(10)->get($url);

            if (! $response->ok()) {
                return null;
            }

            $data = $response->json();
            $results = $data['Results'] ?? [];

            if (empty($results) || ! is_array($results[0] ?? null)) {
                return null;
            }

            $values = $results[0];

            $errorCode = $values['ErrorCode'] ?? '';
            if (in_array($errorCode, ['0', ''], true)) {
                // 0 means no errors; empty sometimes appears too.
                // However, '6 - Incomplete VIN' has ErrorCode '6', still returns data.
                // We still return data unless an unexpected fatal code is present.
            }

            if (str_contains($errorCode, '7')) {
                return null;
            }

            return [
                'vin' => $values['VIN'] ?? $vin,
                'make' => $values['Make'] ?? null,
                'model' => $values['Model'] ?? null,
                'year' => $values['ModelYear'] ?? null,
                'trim' => $values['Trim'] ?? null,
                'body_class' => $values['BodyClass'] ?? null,
                'drive_type' => $values['DriveType'] ?? null,
                'fuel_type_primary' => $values['FuelTypePrimary'] ?? null,
                'engine_cylinders' => $values['EngineCylinders'] ?? null,
                'engine_hp' => $values['EngineHP'] ?? null,
                'displacement_l' => $values['DisplacementL'] ?? null,
                'vehicle_type' => $values['VehicleType'] ?? null,
                'plant_country' => $values['PlantCountry'] ?? null,
                'error_code' => $errorCode,
                'error_text' => $values['ErrorText'] ?? null,
                'manufacturer' => $values['Manufacturer'] ?? null,
                'engine_model' => $values['EngineModel'] ?? null,
            ];
        } catch (ConnectionException) {
            return null;
        } catch (Throwable) {
            return null;
        }
    }

    private function sanitize(string $vin): string
    {
        $vin = strtoupper($vin);
        $vin = preg_replace('/[^A-Z0-9*]/', '', $vin);

        return $vin ?? '';
    }
}
