<?php

namespace App\Infrastructure\External\Providers\Placas;

use App\Domain\Consultas\Services\ProviderAdapterInterface;
use App\Domain\Consultas\ValueObjects\ConsultationRequest;
use App\Domain\Consultas\ValueObjects\ConsultationResponse;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Config;

class PlacasProviderAdapter implements ProviderAdapterInterface
{
    // Fallbacks unicamente si config/providers.php no define los valores; los valores
    // reales usados en runtime vienen de PLACAS_HTTP_TIMEOUT / PLACAS_POLL_MAX_SECONDS /
    // PLACAS_POLL_INTERVAL_SECONDS (ver config/providers.php).
    private const DEFAULT_TIMEOUT = 30;

    private const POLL_MAX_SECONDS = 40;

    private const POLL_INTERVAL_SECONDS = 2;

    private Client $client;

    private int $pollMaxSeconds;

    private int $pollIntervalSeconds;

    public function __construct(?Client $client = null)
    {
        $httpTimeout = (int) Config::get('providers.placas.http_timeout', self::DEFAULT_TIMEOUT);
        $this->pollMaxSeconds = (int) Config::get('providers.placas.poll_max_seconds', self::POLL_MAX_SECONDS);
        $this->pollIntervalSeconds = (int) Config::get('providers.placas.poll_interval_seconds', self::POLL_INTERVAL_SECONDS);

        $this->client = $client ?? new Client([
            'timeout' => $httpTimeout,
            'connect_timeout' => 10,
        ]);
    }

    public function supports(string $adapterCode): bool
    {
        return strtolower($adapterCode) === 'placas';
    }

    public function consult(ConsultationRequest $request): ConsultationResponse
    {
        // Algunos hosting compartidos matan la petición a los 30s; intentamos darle más tiempo.
        // El presupuesto real es POST inicial + ventana de polling (ambos configurables via
        // PLACAS_HTTP_TIMEOUT / PLACAS_POLL_MAX_SECONDS), mas margen de seguridad.
        $httpTimeout = (int) Config::get('providers.placas.http_timeout', self::DEFAULT_TIMEOUT);
        @set_time_limit(max(90, $httpTimeout + $this->pollMaxSeconds + 30));

        [$ok, $message, $value] = $this->validateInput($request->type(), $request->value());
        if (! $ok) {
            return $this->errorResponse(422, $message);
        }

        $token = Config::get('providers.placas.token');
        if (empty($token)) {
            return $this->errorResponse(400, 'Falta configurar PLACAS_API_TOKEN');
        }

        $apiUrl = rtrim(Config::get('providers.placas.url', 'https://placas.info/api/v2/consultar/'), '/').'/';
        $callbackUrl = Config::get('providers.placas.callback_url');

        $payload = [
            'placa_niv' => $value,
            'services' => $request->services(),
        ];
        if (! empty($callbackUrl)) {
            $payload['callback'] = $callbackUrl;
        }

        try {
            $response = $this->client->post($apiUrl, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Token '.$token,
                    'User-Agent' => 'VINTRACK/2.0 (+https://vintrack.com.mx)',
                ],
                'json' => $payload,
            ]);

            $httpStatus = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            $httpStatus = $e->getResponse()?->getStatusCode() ?? 0;
            $body = null;

            return $this->errorResponse($httpStatus, 'No fue posible conectar con el proveedor de placas.');
        }

        if (! is_array($body)) {
            return $this->errorResponse($httpStatus, 'Respuesta no válida de la API de placas');
        }

        if (isset($body['status']) && $body['status'] === 'processing' && isset($body['id'])) {
            [$ok, $pollStatus, $pollErr, $pollData] = $this->pollResult($apiUrl, $body['id'], $token);
            if (! $ok) {
                return $this->errorResponse($pollStatus, $pollErr, $body['id']);
            }
            $body = $pollData;
        }

        $apiId = $body['id'] ?? null;
        $creditsApi = $body['credits'] ?? $body['credits_API'] ?? null;

        [$alert, $flags] = $this->detectTheftFlags($body);

        return new ConsultationResponse(
            true,
            $httpStatus,
            null,
            $body,
            $apiId,
            $flags,
            $creditsApi ? (int) $creditsApi : null
        );
    }

    private function validateInput(string $type, string $value): array
    {
        $type = strtolower(trim($type));
        $value = strtoupper(trim($value));
        $value = preg_replace('/[^A-Z0-9]/', '', $value) ?: '';

        if ($type !== 'placa' && $type !== 'niv') {
            return [false, 'Tipo inválido. Debe ser placa o niv.', $value];
        }

        $nivLength = (int) Config::get('providers.placas.niv_length', 17);
        $placaMin = (int) Config::get('providers.placas.placa_min', 5);
        $placaMax = (int) Config::get('providers.placas.placa_max', 8);

        if ($type === 'niv') {
            if (strlen($value) !== $nivLength) {
                return [false, 'El NIV debe tener exactamente '.$nivLength.' caracteres alfanuméricos.', $value];
            }
        } else {
            $len = strlen($value);
            if ($len < $placaMin || $len > $placaMax) {
                return [false, 'La placa debe tener entre '.$placaMin.' y '.$placaMax.' caracteres alfanuméricos.', $value];
            }
        }

        return [true, null, $value];
    }

    private function pollResult(string $apiUrl, string $id, string $token): array
    {
        $url = rtrim($apiUrl, '/').'/'.$id;
        $deadline = time() + $this->pollMaxSeconds;
        $interval = max(1, $this->pollIntervalSeconds);
        $lastHttp = 0;
        $lastData = null;
        $lastErr = null;

        while (time() < $deadline) {
            try {
                $response = $this->client->get($url, [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Authorization' => 'Token '.$token,
                        'User-Agent' => 'VINTRACK/2.0 (+https://vintrack.com.mx)',
                    ],
                ]);

                $lastHttp = $response->getStatusCode();
                $data = json_decode($response->getBody()->getContents(), true);
            } catch (RequestException $e) {
                $lastHttp = $e->getResponse()?->getStatusCode() ?? 0;
                $lastErr = 'Fallo temporal al consultar el proveedor de placas.';
                sleep($interval);

                continue;
            }

            if (! is_array($data)) {
                $lastErr = 'No-JSON ('.$lastHttp.')';
                sleep($interval);

                continue;
            }

            if (isset($data['repuve']) || isset($data['pgj']) || isset($data['aviso']) || isset($data['ocra']) || isset($data['carfax']) || isset($data['rapi'])) {
                return [true, $lastHttp, null, $data];
            }

            if (isset($data['status']) && $data['status'] !== 'processing') {
                return [true, $lastHttp, null, $data];
            }

            $lastData = $data;
            sleep($interval);
        }

        return [false, $lastHttp, $lastErr ?: 'Timeout esperando resultado', $lastData];
    }

    private function detectTheftFlags(array $data): array
    {
        $flags = [
            'repuve_robo' => 0,
            'pgj_robo' => 0,
            'ocra_robo' => 0,
            'carfax_robo' => 0,
            'rapi_robo' => 0,
        ];

        $pgj = $data['pgj'] ?? null;
        if (is_array($pgj)) {
            $id = isset($pgj['ID_ESTATUS_VHI_ROBO']) ? (int) $pgj['ID_ESTATUS_VHI_ROBO'] : null;
            $st = isset($pgj['ESTATUS_VHI_ROBO']) ? strtoupper((string) $pgj['ESTATUS_VHI_ROBO']) : '';
            if ($id === 1 || strpos($st, 'ROBA') !== false) {
                $flags['pgj_robo'] = 1;
            }
            if ($id === 4 || strpos($st, 'RECUP') !== false) {
                $flags['pgj_robo'] = 1;
            }
        }

        $ocra = $data['ocra'] ?? null;
        if (is_array($ocra)) {
            $rep = $ocra['reporte']['estatus'] ?? '';
            $has = $ocra['conReporteRoboRecuperacion'] ?? '';
            $t = strtoupper(json_encode([$rep, $has]));
            if (strpos($t, 'ROBO') !== false || strpos($t, 'RECUP') !== false || strpos($t, 'TRUE') !== false) {
                $flags['ocra_robo'] = 1;
            }
        }

        $carfax = $data['carfax'] ?? null;
        if ($carfax) {
            $txt = strtoupper(json_encode($carfax));
            if (strpos($txt, 'THEFT') !== false || strpos($txt, 'STOLEN') !== false || strpos($txt, 'ROBO') !== false) {
                $flags['carfax_robo'] = 1;
            }
        }

        $rapi = $data['rapi'] ?? null;
        if (is_array($rapi)) {
            if (array_key_exists('tiene_delito', $rapi)) {
                $v = $rapi['tiene_delito'];
                $truthy = is_bool($v) ? $v : (is_string($v) ? (strcasecmp($v, 'true') === 0 || $v === '1' || strcasecmp($v, 'si') === 0 || strcasecmp($v, 'sí') === 0) : ((int) $v === 1));
                if ($truthy) {
                    $flags['rapi_robo'] = 1;
                }
            }
        }

        $repuve = $data['repuve'] ?? null;
        if ($repuve) {
            $txt = strtoupper(json_encode($repuve));
            if (strpos($txt, 'ROBO') !== false || strpos($txt, 'ROBADO') !== false) {
                $flags['repuve_robo'] = 1;
            }
        }

        $alerta = ($flags['repuve_robo'] || $flags['pgj_robo'] || $flags['ocra_robo'] || $flags['carfax_robo'] || $flags['rapi_robo']) ? 1 : 0;

        return [$alerta, $flags];
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
                'repuve_robo' => 0,
                'pgj_robo' => 0,
                'ocra_robo' => 0,
                'carfax_robo' => 0,
                'rapi_robo' => 0,
            ]
        );
    }
}
