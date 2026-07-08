<?php

namespace App\Presentation\Http\Controllers\Web;

use App\Application\Consultas\Services\ConsultationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConsultationController
{
    public function __construct(
        private readonly ConsultationService $consultationService
    ) {
    }

    public function consult(Request $request)
    {
        $data = $request->validate([
            'provider' => 'required|string|max:32',
            'type' => 'required|string|in:placa,niv,vin',
            'value' => 'required|string|max:32',
            'services' => 'nullable|array',
            'services.*' => 'string|max:32',
        ]);

        $services = $data['services'] ?? match (strtoupper($data['provider'])) {
            'VINDATA' => ['VHR'],
            default => ['repuve', 'pgj', 'aviso', 'ocra', 'carfax', 'rapi'],
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

        return response()->json([
            'success' => $response->success(),
            'status' => $response->httpStatus(),
            'message' => $response->errorMessage(),
            'data' => $responseData,
            'report_url' => $reportUrl,
            'local_report_url' => $localReportUrl,
            'theft_flags' => $response->theftFlags(),
        ], $response->httpStatus() ?: 200);
    }
}
