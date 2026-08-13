<?php

namespace App\Presentation\Http\Controllers\Web;

use App\Application\NotificationCases\Exceptions\CaseDocumentException;
use App\Application\NotificationCases\Services\NotificationCaseDocumentService;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\NotificationCase;
use App\Infrastructure\Persistence\Models\NotificationCaseDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class NotificationCaseDocumentController extends Controller
{
    public function __construct(private readonly NotificationCaseDocumentService $documents) {}

    public function store(Request $request, NotificationCase $case): JsonResponse
    {
        try {
            $file = $request->file('document');
            if ($file === null) {
                throw new CaseDocumentException('DOCUMENT_REQUIRED', 'Debe adjuntar un documento.');
            }
            $document = $this->documents->upload($case, $request->user(), $file, (string) $request->header('Idempotency-Key'), $this->correlation($request), $request->ip(), $request->userAgent());

            return response()->json(['data' => ['id' => $document->id, 'original_name' => $document->original_name, 'mime_type' => $document->mime_type, 'size_bytes' => $document->size_bytes]], 201);
        } catch (CaseDocumentException $exception) {
            return response()->json(['error' => $exception->errorCode, 'message' => $exception->getMessage()], $exception->httpStatus);
        }
    }

    public function index(Request $request, NotificationCase $case): JsonResponse
    {
        try {
            return response()->json(['data' => $this->documents->list($case, $request->user())]);
        } catch (CaseDocumentException $exception) {
            return response()->json(['error' => $exception->errorCode, 'message' => $exception->getMessage()], $exception->httpStatus);
        }
    }

    public function show(Request $request, NotificationCase $case, NotificationCaseDocument $document): StreamedResponse|JsonResponse
    {
        try {
            return $this->documents->download($case, $document, $request->user());
        } catch (CaseDocumentException $exception) {
            return response()->json(['error' => $exception->errorCode, 'message' => $exception->getMessage()], $exception->httpStatus);
        }
    }

    public function destroy(Request $request, NotificationCase $case, NotificationCaseDocument $document): JsonResponse
    {
        try {
            $removed = $this->documents->remove($case, $document, $request->user(), (string) $request->header('Idempotency-Key'), $this->correlation($request), $request->ip(), $request->userAgent());

            return response()->json(['data' => ['id' => $removed->id, 'removed' => true]]);
        } catch (CaseDocumentException $exception) {
            return response()->json(['error' => $exception->errorCode, 'message' => $exception->getMessage()], $exception->httpStatus);
        }
    }

    private function correlation(Request $request): string
    {
        $value = (string) $request->header('X-Correlation-ID');

        return $value !== '' && strlen($value) <= 128 ? $value : (string) Str::uuid();
    }
}
