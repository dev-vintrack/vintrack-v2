<?php

namespace App\Presentation\Http\Controllers\Web;

use App\Application\Consultas\Services\ConsultationService;
use App\Presentation\Support\PlacasReportPresenter;
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
                'provider' => 'required|string|max:32',
                'type' => 'required|string|in:placa,niv,vin',
                'value' => 'required|string|max:32',
                'services' => 'nullable|array',
                'services.*' => 'string|max:32',
            ]);

            $services = $data['services'] ?? match (strtoupper($data['provider'])) {
                'VINDATA' => ['VHR'],
                default => ['Placas_Service'],
            };

            $result = $this->consultationService->consult(
                Auth::id(),
                $data['provider'],
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
            if ($response->success() && strtoupper($data['provider']) === 'PLACAS') {
                $sections = PlacasReportPresenter::sections($responseData);
                $alertaRobo = in_array(1, array_map('intval', $response->theftFlags()), true);
                $banner = PlacasReportPresenter::computeBanner($sections, $alertaRobo);
            }

            // Usamos siempre un status HTTP que permita body (evita 204/304 que lo vacían).
            $httpStatus = $response->success() ? 200 : 422;

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
