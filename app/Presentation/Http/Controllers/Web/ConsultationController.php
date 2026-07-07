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
            'type' => 'required|string|in:placa,niv',
            'value' => 'required|string|max:32',
            'services' => 'nullable|array',
            'services.*' => 'string|max:32',
        ]);

        $services = $data['services'] ?? ['repuve', 'pgj', 'aviso', 'ocra', 'carfax', 'rapi'];

        $response = $this->consultationService->consult(
            Auth::id(),
            $data['provider'],
            $data['type'],
            $data['value'],
            $services
        );

        return response()->json([
            'success' => $response->success(),
            'status' => $response->httpStatus(),
            'message' => $response->errorMessage(),
            'data' => $response->data(),
            'theft_flags' => $response->theftFlags(),
        ], $response->httpStatus() ?: 200);
    }
}
