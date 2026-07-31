<?php

namespace App\Presentation\Http\Controllers\Web;

use App\Application\Consultas\Services\ConsultationService;
use App\Infrastructure\Persistence\Models\Provider;
use App\Infrastructure\Persistence\Models\ProviderService;
use App\Presentation\Support\PlacasReportPresenter;
use App\Presentation\Support\RoleHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class ConsultationController
{
    public function __construct(
        private readonly ConsultationService $consultationService
    ) {
    }

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

            $services = $data['services'] ?? match ($adapterCode) {
                'vindata' => ['VHR'],
                default => ['Placas_Service'],
            };

            $providerService = ProviderService::where('provider_id', $provider->id)
                ->where('key', $services[0] ?? '')
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
                $services
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

            // Usamos el status real del servicio; evitamos 204/304 que vacían el body.
            $httpStatus = $response->success() ? 200 : $response->httpStatus();

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
        } catch (Throwable $e) {
            Log::error('Error en consulta: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Error interno: ' . $e->getMessage(),
                'data' => [],
                'report_url' => null,
                'local_report_url' => null,
                'theft_flags' => [],
                'banner' => null,
            ], 500);
        }
    }
}
