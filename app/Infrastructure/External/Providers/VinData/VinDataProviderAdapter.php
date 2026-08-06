<?php

namespace App\Infrastructure\External\Providers\VinData;

use App\Domain\Consultas\Services\ProviderAdapterInterface;
use App\Domain\Consultas\ValueObjects\ConsultationRequest;
use App\Domain\Consultas\ValueObjects\ConsultationResponse;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use RuntimeException;

class VinDataProviderAdapter implements ProviderAdapterInterface
{
    private const TOKEN_CACHE_KEY = 'vindata_bearer_token';
    private const DEFAULT_TIMEOUT = 60;

    private Client $client;

    public function __construct(?Client $client = null)
    {
        $this->client = $client ?? new Client([
            'timeout' => (int) Config::get('providers.vindata.http_timeout', self::DEFAULT_TIMEOUT),
            'connect_timeout' => 10,
        ]);
    }

    public function supports(string $adapterCode): bool
    {
        return strtolower($adapterCode) === 'vindata';
    }

    public function consult(ConsultationRequest $request): ConsultationResponse
    {
        $type = strtolower(trim($request->type()));
        if ($type !== 'vin') {
            return $this->errorResponse(422, 'VINData solo acepta consultas por VIN.');
        }

        $vin = strtoupper(trim($request->value()));
        if (strlen($vin) !== 17) {
            return $this->errorResponse(422, 'El VIN debe tener exactamente 17 caracteres alfanuméricos.');
        }

        $services = $request->services();
        if (empty($services)) {
            return $this->errorResponse(422, 'Debe seleccionar un producto VINData.');
        }

        $serviceCode = strtolower(trim($services[0]));
        $productMap = [
            'vhr' => 'VHR',
            'nmvtis_plus' => 'NMVTISPlus',
        ];
        if (!array_key_exists($serviceCode, $productMap)) {
            return $this->errorResponse(422, 'Producto VINData inválido. Use VHR o NMVTISPlus.');
        }

        $apiProductCode = $productMap[$serviceCode];

        $token = $this->getToken();
        if (empty($token)) {
            return $this->errorResponse(500, 'No se pudo obtener token de autenticación de VINData.');
        }

        $baseUrl = rtrim(Config::get('providers.vindata.url', 'https://api.vindata.com/v1'), '/');

        // Step 1: Buy report
        $buyUrl = "{$baseUrl}/products/{$apiProductCode}/reports/{$vin}?force=false";

        try {
            $buyResponse = $this->client->post($buyUrl, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                    'User-Agent' => 'VINTRACK/2.0 (+https://vintrack.com.mx)',
                ],
            ]);

            $buyBody = json_decode($buyResponse->getBody()->getContents(), true);
        } catch (RequestException $e) {
            return $this->errorResponse(
                $e->getResponse()?->getStatusCode() ?? 500,
                'Error al comprar reporte VINData: ' . $e->getMessage()
            );
        }

        if (empty($buyBody['uuid'])) {
            return $this->errorResponse(500, 'La respuesta de VINData no contiene el UUID del reporte.');
        }

        $uuid = $buyBody['uuid'];

        // Step 2: Get raw report data
        $reportUrl = "{$baseUrl}/reports/{$uuid}";

        try {
            $reportResponse = $this->client->get($reportUrl, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                    'User-Agent' => 'VINTRACK/2.0 (+https://vintrack.com.mx)',
                ],
            ]);

            $reportBody = json_decode($reportResponse->getBody()->getContents(), true);
        } catch (RequestException $e) {
            return $this->errorResponse(
                $e->getResponse()?->getStatusCode() ?? 500,
                'Error al obtener el reporte VINData: ' . $e->getMessage(),
                $uuid
            );
        }

        $data = array_merge($buyBody, ['rawData' => $reportBody]);

        return new ConsultationResponse(
            true,
            200,
            null,
            $data,
            $uuid,
            $this->detectAlerts($reportBody)
        );
    }

    private function getToken(): ?string
    {
        $cached = Cache::get(self::TOKEN_CACHE_KEY);
        if (!empty($cached)) {
            return $cached;
        }

        $baseUrl = rtrim(Config::get('providers.vindata.url', 'https://api.vindata.com/v1'), '/');
        $tokenUrl = "{$baseUrl}/token";

        $payload = [
            'secret_key' => Config::get('providers.vindata.secret_key'),
            'username' => Config::get('providers.vindata.username'),
            'password' => Config::get('providers.vindata.password'),
        ];

        try {
            $response = $this->client->post($tokenUrl, [
                'headers' => [
                    'Accept' => 'application/json',
                    'User-Agent' => 'VINTRACK/2.0 (+https://vintrack.com.mx)',
                ],
                'json' => $payload,
            ]);

            $body = json_decode($response->getBody()->getContents(), true);
            $token = $body['token'] ?? $body['access_token'] ?? null;

            if (!empty($token)) {
                $ttl = (int) Config::get('providers.vindata.token_ttl_minutes', 55);
                Cache::put(self::TOKEN_CACHE_KEY, $token, now()->addMinutes($ttl));
            }

            return $token;
        } catch (RequestException $e) {
            return null;
        }
    }

    private function detectAlerts(array $reportBody): array
    {
        $alerts = [
            'active_theft' => 0,
            'open_lien' => 0,
            'junk_salvage' => 0,
            'odometer_issue' => 0,
        ];

        $otherInformation = $reportBody['otherInformation'] ?? [];
        foreach ($otherInformation as $info) {
            $event = strtolower($info['event'] ?? '');

            if (str_contains($event, 'theft') || str_contains($event, 'robo') || str_contains($event, 'stolen')) {
                $alerts['active_theft'] = 1;
            }
            if (str_contains($event, 'lien')) {
                $alerts['open_lien'] = 1;
            }
            if (str_contains($event, 'junk') || str_contains($event, 'salvage') || str_contains($event, 'total loss')) {
                $alerts['junk_salvage'] = 1;
            }
            if (str_contains($event, 'odometer') || str_contains($event, 'mileage')) {
                $alerts['odometer_issue'] = 1;
            }
        }

        if (!empty($reportBody['titleBrandReported'])) {
            foreach ($reportBody['titleBrandReported'] as $brand) {
                $brandName = strtolower($brand['name'] ?? '');
                if (str_contains($brandName, 'junk') || str_contains($brandName, 'salvage') || str_contains($brandName, 'total loss')) {
                    $alerts['junk_salvage'] = 1;
                }
                if (str_contains($brandName, 'odometer') || str_contains($brandName, 'mileage')) {
                    $alerts['odometer_issue'] = 1;
                }
            }
        }

        if (!empty($reportBody['junkSalvageTotalLoss'])) {
            $alerts['junk_salvage'] = 1;
        }

        return $alerts;
    }

    private function errorResponse(int $status, string $message, ?string $apiId = null): ConsultationResponse
    {
        return new ConsultationResponse(
            false,
            $status,
            $message,
            [],
            $apiId,
            [
                'active_theft' => 0,
                'open_lien' => 0,
                'junk_salvage' => 0,
                'odometer_issue' => 0,
            ]
        );
    }
}
