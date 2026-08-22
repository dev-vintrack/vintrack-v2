<?php

namespace App\Application\NotificationCases\Services;

use App\Domain\NotificationCases\Enums\MalwareScanStatus;
use App\Infrastructure\Persistence\Models\NotificationCase;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;

final class NotificationCasePdfExportService
{
    private const IMAGE_MIMES = ['image/jpeg', 'image/png'];

    public function __construct(private readonly PrivateCaseDocumentStorage $storage) {}

    public function download(NotificationCase $case): Response
    {
        $case->loadMissing(['owner', 'consultation', 'documents' => fn ($query) => $query->whereNull('removed_at')->orderBy('id')]);
        $images = $case->documents
            ->filter(fn ($document) => $document->malware_scan_status === MalwareScanStatus::CLEAN && in_array($document->mime_type, self::IMAGE_MIMES, true))
            ->map(fn ($document) => $this->imageData($document->storage_key, $document->mime_type, $document->original_name))
            ->filter()
            ->values()
            ->all();

        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('admin.notification-cases.pdf', compact('case', 'images'))->render());
        $dompdf->setPaper('letter');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="expediente-'.strtolower($case->case_number).'.pdf"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function imageData(string $key, string $mime, string $name): ?array
    {
        $stream = $this->storage->readStream($key);
        if (! is_resource($stream)) {
            return null;
        }
        $contents = stream_get_contents($stream);
        fclose($stream);
        if (! is_string($contents) || $contents === '' || ! $this->isMatchingImage($contents, $mime)) {
            return null;
        }

        return ['name' => $name, 'src' => 'data:'.$mime.';base64,'.base64_encode($contents)];
    }

    private function isMatchingImage(string $contents, string $mime): bool
    {
        $image = @getimagesizefromstring($contents);

        return is_array($image) && ($image['mime'] ?? null) === $mime;
    }
}
