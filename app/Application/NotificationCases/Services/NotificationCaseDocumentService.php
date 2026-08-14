<?php

namespace App\Application\NotificationCases\Services;

use App\Application\NotificationCases\Exceptions\CaseDocumentException;
use App\Domain\NotificationCases\Enums\MalwareScanStatus;
use App\Infrastructure\Persistence\Models\NotificationCase;
use App\Infrastructure\Persistence\Models\NotificationCaseDocument;
use App\Infrastructure\Persistence\Models\NotificationCaseEvent;
use App\Models\User;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class NotificationCaseDocumentService
{
    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly NotificationCaseSettings $settings,
        private readonly NotificationCaseAuthorizationService $authorization,
        private readonly NotificationCaseAuditService $audit,
        private readonly CaseDocumentValidator $validator,
        private readonly PrivateCaseDocumentStorage $storage,
    ) {}

    public function upload(NotificationCase $case, User $actor, UploadedFile $file, string $requestKey, string $correlationId, ?string $ip = null, ?string $userAgent = null): NotificationCaseDocument
    {
        $this->assertRequestKey($requestKey);
        $metadata = $this->validator->validate($file, $this->settings->maxFileBytes());
        $eventKey = 'document-upload:'.hash('sha256', $case->id.'|'.$actor->id.'|'.$requestKey);
        $storedKey = null;

        try {
            return $this->db->transaction(function () use ($case, $actor, $file, $metadata, $eventKey, $requestKey, $correlationId, $ip, $userAgent, &$storedKey): NotificationCaseDocument {
                $lockedCase = NotificationCase::query()->lockForUpdate()->findOrFail($case->id);
                if (! $this->authorization->canManageDocuments($actor, $lockedCase)) {
                    throw new CaseDocumentException('DOCUMENT_ACTION_FORBIDDEN', 'No tiene autorización para modificar documentos de este expediente.', 403);
                }

                $prior = NotificationCaseEvent::query()->where('event_key', $eventKey)->first();
                $priorId = $prior?->metadata['document_id'] ?? null;
                if (is_numeric($priorId)) {
                    return NotificationCaseDocument::query()->where('notification_case_id', $lockedCase->id)->findOrFail((int) $priorId);
                }

                $active = NotificationCaseDocument::query()->where('notification_case_id', $lockedCase->id)->whereNull('removed_at')->count();
                if ($active >= $this->settings->maxFiles()) {
                    throw new CaseDocumentException('MAX_ACTIVE_DOCUMENTS', 'El expediente alcanzó el máximo de documentos activos.', 409);
                }

                $storedKey = $this->storage->store($file, $metadata['extension']);
                $now = now();
                $document = new NotificationCaseDocument([
                    'uploaded_by_user_id' => $actor->id,
                    'original_name' => $metadata['original_name'],
                    'mime_type' => $metadata['mime_type'],
                    'extension' => $metadata['extension'],
                    'size_bytes' => $metadata['size_bytes'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $document->notification_case_id = $lockedCase->id;
                $document->storage_disk = PrivateCaseDocumentStorage::DISK;
                $document->storage_key = $storedKey;
                $document->sha256 = $metadata['sha256'];
                $document->malware_scan_status = MalwareScanStatus::PENDING;
                $document->save();

                $this->audit->record($lockedCase->id, 'DOCUMENT_UPLOADED', $eventKey, $correlationId, $actor->id, (string) $actor->rol, metadata: [
                    'document_id' => $document->id,
                    'mime_type' => $document->mime_type,
                    'extension' => $document->extension,
                    'size_bytes' => $document->size_bytes,
                    'sha256' => $document->sha256,
                    'malware_scan_status' => MalwareScanStatus::PENDING->value,
                ], requestKey: $requestKey, ipAddress: $ip, userAgent: $userAgent);

                return $document;
            }, 3);
        } catch (Throwable $exception) {
            if (is_string($storedKey)) {
                $this->storage->delete($storedKey);
            }
            throw $exception;
        }
    }

    /** @return list<array{id:int,original_name:string,mime_type:string,extension:string,size_bytes:int,created_at:?string}> */
    public function list(NotificationCase $case, User $actor): array
    {
        if (! $this->authorization->canListDocuments($actor, $case)) {
            throw new CaseDocumentException('DOCUMENT_ACTION_FORBIDDEN', 'No tiene autorización para consultar estos documentos.', 403);
        }

        return NotificationCaseDocument::query()->where('notification_case_id', $case->id)->whereNull('removed_at')->orderBy('id')->get()
            ->map(fn (NotificationCaseDocument $document): array => [
                'id' => $document->id,
                'original_name' => $document->original_name,
                'mime_type' => $document->mime_type,
                'extension' => $document->extension,
                'size_bytes' => $document->size_bytes,
                'malware_scan_status' => $document->malware_scan_status->value,
                'download_available' => $document->malware_scan_status->allowsOrdinaryUse(),
                'created_at' => $document->created_at?->format('Y-m-d H:i:s'),
            ])->all();
    }

    public function download(NotificationCase $case, NotificationCaseDocument $document, User $actor): StreamedResponse
    {
        $this->assertDocumentBelongs($case, $document);
        if (! $this->authorization->canListDocuments($actor, $case) || $document->removed_at !== null) {
            throw new CaseDocumentException('DOCUMENT_NOT_AVAILABLE', 'El documento no está disponible.', 404);
        }
        if (! $document->malware_scan_status->allowsOrdinaryUse()) {
            throw new CaseDocumentException('DOCUMENT_QUARANTINED', 'El documento permanece en cuarentena.', 423);
        }
        if (! $this->storage->exists($document->storage_key)) {
            throw new CaseDocumentException('DOCUMENT_FILE_MISSING', 'El archivo no está disponible.', 404);
        }

        $safeName = trim((string) preg_replace('~[\x00-\x1F\x7F\\/]+~u', '_', $document->original_name), ' .');
        $safeName = mb_substr($safeName !== '' ? $safeName : 'evidencia.'.$document->extension, 0, 180);

        return $this->storage->download($document->storage_key, $safeName, $document->mime_type);
    }

    public function remove(NotificationCase $case, NotificationCaseDocument $document, User $actor, string $requestKey, string $correlationId, ?string $ip = null, ?string $userAgent = null): NotificationCaseDocument
    {
        $this->assertRequestKey($requestKey);
        $this->assertDocumentBelongs($case, $document);

        return $this->db->transaction(function () use ($case, $document, $actor, $requestKey, $correlationId, $ip, $userAgent): NotificationCaseDocument {
            $lockedCase = NotificationCase::query()->lockForUpdate()->findOrFail($case->id);
            if (! $this->authorization->canManageDocuments($actor, $lockedCase)) {
                throw new CaseDocumentException('DOCUMENT_ACTION_FORBIDDEN', 'No tiene autorización para modificar documentos de este expediente.', 403);
            }
            $locked = NotificationCaseDocument::query()->where('notification_case_id', $lockedCase->id)->lockForUpdate()->findOrFail($document->id);
            if ($locked->removed_at !== null) {
                return $locked;
            }

            $locked->removed_at = now();
            $locked->removed_by_user_id = $actor->id;
            $locked->updated_at = now();
            $locked->save();
            $this->audit->record($lockedCase->id, 'DOCUMENT_REMOVED', 'document-remove:'.$locked->id, $correlationId, $actor->id, (string) $actor->rol, metadata: ['document_id' => $locked->id], requestKey: $requestKey, ipAddress: $ip, userAgent: $userAgent);

            return $locked;
        }, 3);
    }

    private function assertDocumentBelongs(NotificationCase $case, NotificationCaseDocument $document): void
    {
        if ($document->notification_case_id !== $case->id) {
            throw new CaseDocumentException('DOCUMENT_NOT_AVAILABLE', 'El documento no está disponible.', 404);
        }
    }

    private function assertRequestKey(string $requestKey): void
    {
        if ($requestKey === '' || strlen($requestKey) > 128 || ! Str::isAscii($requestKey)) {
            throw new CaseDocumentException('INVALID_IDEMPOTENCY_KEY', 'Se requiere una clave de idempotencia válida.');
        }
    }
}
