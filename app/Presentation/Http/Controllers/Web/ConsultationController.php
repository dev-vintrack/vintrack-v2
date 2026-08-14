<?php

namespace App\Presentation\Http\Controllers\Web;

use App\Application\Consultas\Exceptions\ConsultationOperationException;
use App\Application\Consultas\Services\ConsultationService;
use App\Application\NotificationCases\Exceptions\ConsultationBlockedException;
use App\Infrastructure\Persistence\Models\Provider;
use App\Infrastructure\Persistence\Models\ProviderService;
use App\Presentation\Support\PlacasReportPresenter;
use App\Presentation\Support\RoleHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ConsultationController
{
    public function __construct(
        private readonly ConsultationService $consultationService
    ) {}

    public function consult(Request $request)
    {
        try {
            $data = $request->validate([
                'provider_id' => 'required|integer|exists:providers,id',
                'type' => 'required|string|in:placa,niv,vin',
                'value' => 'required|string|max:32',
                'services' => 'nullable|array',
                'services.*' => 'string|max:32',
            ]);

            $provider = Provider::findOrFail($data['provider_id']);
            $adapterCode = strtolower($provider->adapter_code);

            $serviceCodes = $data['services'] ?? match ($adapterCode) {
                'vindata' => ['vhr'],
                default => ['placas_service'],
            };

            $providerService = ProviderService::where('provider_id', $provider->id)
                ->where('service_code', $serviceCodes[0] ?? '')
                ->first();

            if (! $providerService || ! RoleHelper::isServiceAllowed(Auth::user()?->id_rol, $providerService->id)) {
                return response()->json([
                    'success' => false,
                    'status' => 403,
                    'message' => 'Servicio no permitido para tu rol.',
                    'data' => [],
                    'report_url' => null,
                    'local_report_url' => null,
                    'theft_flags' => [],
                    'banner' => null,
                ], 403);
            }

            $result = $this->consultationService->consult(
                Auth::id(),
                $provider->id,
                $data['type'],
                $data['value'],
                $serviceCodes,
                (string) ($request->header('X-Idempotency-Key') ?: Str::uuid()),
                $request->ip(),
                $request->userAgent(),
            );

            $response = $result->response();
            $responseData = $response->data();
            $reportUrl = $responseData['htmlReport'] ?? $responseData['pdfReport'] ?? $responseData['rawReport'] ?? null;

            $localReportUrl = null;
            $consultation = $result->consultation();
            if ($consultation->id() !== null && $consultation->id() > 0) {
                $localReportUrl = route('reports.show', $consultation->id());
            }

            $banner = null;
            if ($response->success() && $adapterCode === 'placas') {
                $sections = PlacasReportPresenter::sections($responseData);
                $alertaRobo = in_array(1, array_map('intval', $response->theftFlags()), true);
                $banner = PlacasReportPresenter::computeBanner($sections, $alertaRobo);
            }

            // Usamos el status real del servicio, pero normalizado: codigos invalidos o que
            // vacian el body (0, 1xx, 204, 304, fuera de 100-599) rompen el JSON en el cliente
            // ("Unexpected end of JSON input") porque Response::prepare() los trata como vacios.
            // El status real del proveedor sigue disponible en el body via 'status'.
            $httpStatus = $response->success() ? 200 : $this->safeHttpStatus($response->httpStatus());

            if (! $response->success()) {
                // A diferencia de las excepciones (catch abajo), estos fallos "controlados"
                // (timeout de polling, saldo insuficiente, proveedor deshabilitado, etc.) no
                // quedaban registrados en ningun log, dificultando el diagnostico en produccion.
                Log::warning('Consulta fallida (sin excepcion)', [
                    'adapter' => $adapterCode,
                    'provider_id' => $provider->id,
                    'provider_service_id' => $providerService->id,
                    'user_id' => Auth::id(),
                    'upstream_status' => $response->httpStatus(),
                    'response_http_status' => $httpStatus,
                    'message' => $response->errorMessage(),
                ]);
            }

            return response()->json([
                'success' => $response->success(),
                'status' => $response->httpStatus(),
                'message' => $response->errorMessage(),
                'data' => $responseData,
                'report_url' => $reportUrl,
                'local_report_url' => $localReportUrl,
                'theft_flags' => $response->theftFlags(),
                'banner' => $banner,
            ], $httpStatus);
        } catch (ConsultationOperationException $e) {
            return response()->json([
                'success' => false, 'status' => 409, 'code' => $e->errorCode,
                'message' => $e->getMessage(), 'data' => [], 'report_url' => null,
                'local_report_url' => null, 'theft_flags' => [], 'banner' => null,
            ], 409);
        } catch (ConsultationBlockedException $e) {
            return response()->json([
                'success' => false,
                'status' => 409,
                'code' => 'MAX_PENDING_NOTIFICATION_CASES',
                'message' => $e->getMessage(),
                'pending_count' => $e->pendingCount,
                'data' => [],
                'report_url' => null,
                'local_report_url' => null,
                'theft_flags' => [],
                'banner' => null,
            ], 409);
        } catch (Throwable $e) {
            Log::error('Error interno durante consulta.', [
                'exception' => get_class($e),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'No fue posible completar la consulta.',
                'data' => [],
                'report_url' => null,
                'local_report_url' => null,
                'theft_flags' => [],
                'banner' => null,
            ], 500);
        }
    }

    /**
     * Normaliza un status HTTP proveniente de un servicio externo para usarlo como
     * codigo de respuesta HTTP propio. Symfony/Laravel vacian automaticamente el body
     * de la respuesta cuando el status es informativo (1xx) o pertenece a {204, 304}
     * (Response::isEmpty()), y ademas rechazan cualquier valor fuera de 100-599
     * (Response::isInvalid()). Cualquiera de esos casos produciria un body vacio o una
     * excepcion, causando "Unexpected end of JSON input" en el cliente.
     */
    private function safeHttpStatus(int $status): int
    {
        if ($status < 200 || $status > 599 || in_array($status, [204, 304], true)) {
            return 502;
        }

        return $status;
    }
}
