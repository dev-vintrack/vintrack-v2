<?php

namespace App\Presentation\Http\Controllers\Web;

use App\Domain\Consultas\Repositories\ConsultationRepositoryInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ReportController
{
    public function __construct(
        private readonly ConsultationRepositoryInterface $consultationRepository
    ) {
    }

    public function show(int $id): Response
    {
        $consultation = $this->consultationRepository->findById($id);
        if (!$consultation || $consultation->userId() !== Auth::id()) {
            throw new NotFoundHttpException('Reporte no encontrado.');
        }

        $rawData = $consultation->responseJson()['rawData'] ?? [];

        return response()->view('reports.vin_data', [
            'consultation' => $consultation,
            'rawData' => $rawData,
            'summary' => $rawData['summary'] ?? [],
            'otherInformation' => $rawData['otherInformation'] ?? [],
            'titleInformation' => $rawData['titleInformation'] ?? [],
            'odometerInformation' => $rawData['odometerInformation'] ?? [],
            'titleBrandReported' => $rawData['titleBrandReported'] ?? [],
            'junkSalvageTotalLoss' => $rawData['junkSalvageTotalLoss'] ?? [],
            'trimLevels' => $rawData['trimLevels'] ?? [],
            'reportSummary' => $rawData['reportSummary'] ?? [],
            'vehicleInfo' => $consultation->responseJson(),
        ]);
    }

    public function pdf(int $id): Response
    {
        $consultation = $this->consultationRepository->findById($id);
        if (!$consultation || $consultation->userId() !== Auth::id()) {
            throw new NotFoundHttpException('Reporte no encontrado.');
        }

        $rawData = $consultation->responseJson()['rawData'] ?? [];

        $html = view('reports.vin_data_pdf', [
            'consultation' => $consultation,
            'rawData' => $rawData,
            'summary' => $rawData['summary'] ?? [],
            'otherInformation' => $rawData['otherInformation'] ?? [],
            'titleInformation' => $rawData['titleInformation'] ?? [],
            'odometerInformation' => $rawData['odometerInformation'] ?? [],
            'titleBrandReported' => $rawData['titleBrandReported'] ?? [],
            'junkSalvageTotalLoss' => $rawData['junkSalvageTotalLoss'] ?? [],
            'trimLevels' => $rawData['trimLevels'] ?? [],
            'reportSummary' => $rawData['reportSummary'] ?? [],
            'vehicleInfo' => $consultation->responseJson(),
        ])->render();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="reporte-vintrack-' . $id . '.pdf"',
        ]);
    }
}
