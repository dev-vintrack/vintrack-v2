<?php

namespace App\Presentation\Http\Controllers\Web;

use App\Domain\Consultas\Entities\Consultation;
use App\Domain\Consultas\Repositories\ConsultationRepositoryInterface;
use App\Domain\Providers\Repositories\ProviderRepositoryInterface;
use App\Domain\Providers\ValueObjects\ProviderId;
use App\Presentation\Support\PlacasReportPresenter;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ReportController
{
    public function __construct(
        private readonly ConsultationRepositoryInterface $consultationRepository,
        private readonly ProviderRepositoryInterface $providerRepository
    ) {
    }

    public function show(int $id): Response
    {
        $consultation = $this->findOwnedConsultation($id);

        if ($this->isPlacas($consultation)) {
            return response()->view('reports.placas', $this->placasViewData($consultation));
        }

        return response()->view('reports.vin_data', $this->vinDataViewData($consultation));
    }

    public function pdf(int $id): Response
    {
        $consultation = $this->findOwnedConsultation($id);

        $isPlacas = $this->isPlacas($consultation);
        $viewName = $isPlacas ? 'reports.placas_pdf' : 'reports.vin_data_pdf';
        $viewData = $isPlacas ? $this->placasViewData($consultation) : $this->vinDataViewData($consultation);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $viewData['qrUrl'] = 'https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=' . urlencode('https://vintrack.com.mx/');
        $viewData['reportId'] = $consultation->apiId() ?? (string) $consultation->id();
        $viewData['generatedAt'] = $consultation->createdAt()->format('m/d/Y');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view($viewName, $viewData)->render());
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $criterio = preg_replace('/[^A-Za-z0-9_-]/', '_', $consultation->criterio());
        $valor = preg_replace('/[^A-Za-z0-9_-]/', '_', $consultation->valor());
        $filename = 'reporte-vintrack-' . $criterio . '-' . $valor . '.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    private function findOwnedConsultation(int $id): Consultation
    {
        $consultation = $this->consultationRepository->findById($id);
        if (!$consultation || $consultation->userId() !== Auth::id()) {
            throw new NotFoundHttpException('Reporte no encontrado.');
        }

        return $consultation;
    }

    private function isPlacas(Consultation $consultation): bool
    {
        $provider = $this->providerRepository->findById(ProviderId::fromInt($consultation->providerId()));

        return $provider !== null && strtoupper($provider->code()->value()) === 'PLACAS';
    }

    /**
     * @return array<string, mixed>
     */
    private function placasViewData(Consultation $consultation): array
    {
        $rawData = $consultation->responseJson();
        $sections = PlacasReportPresenter::sections($rawData);
        $banner = PlacasReportPresenter::computeBanner($sections, $consultation->alertaRobo());

        return [
            'consultation' => $consultation,
            'sections' => $sections,
            'banner' => $banner,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function vinDataViewData(Consultation $consultation): array
    {
        $responseJson = $this->normalizeVinDataBranding($consultation->responseJson());
        $rawData = $responseJson['rawData'] ?? [];

        return [
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
            'vehicleInfo' => $responseJson,
        ];
    }

    private function normalizeVinDataBranding(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->normalizeVinDataBranding($value);
            } elseif (is_string($value)) {
                $result[$key] = str_replace('VINData', 'VINTrack', $value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
