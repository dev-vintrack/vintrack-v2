<?php

namespace App\Application\NotificationCases\Services;

use App\Application\NotificationCases\Exceptions\CaseDocumentException;
use Illuminate\Http\UploadedFile;

final class CaseDocumentValidator
{
    private const ALLOWED = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
    ];

    /** @return array{extension:string,mime_type:string,size_bytes:int,sha256:string,original_name:string} */
    public function validate(UploadedFile $file, int $maxBytes): array
    {
        if (! $file->isValid()) {
            throw new CaseDocumentException('INVALID_UPLOAD', 'El archivo no pudo cargarse correctamente.');
        }

        $size = $file->getSize();
        if (! is_int($size) || $size < 1 || $size > $maxBytes) {
            throw new CaseDocumentException('INVALID_FILE_SIZE', 'El archivo excede el límite permitido o está vacío.');
        }

        $name = $file->getClientOriginalName();
        if ($name === '' || mb_strlen($name) > 255 || str_contains($name, "\r") || str_contains($name, "\n")) {
            throw new CaseDocumentException('INVALID_FILE_NAME', 'El nombre del archivo no es válido.');
        }

        $extension = mb_strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $mime = $file->getMimeType();
        if (! isset(self::ALLOWED[$extension]) || self::ALLOWED[$extension] !== $mime) {
            throw new CaseDocumentException('INVALID_FILE_TYPE', 'El tipo real y la extensión del archivo no están permitidos.');
        }

        $path = $file->getRealPath();
        if (! is_string($path) || ! $this->signatureMatches($path, $mime)) {
            throw new CaseDocumentException('INVALID_FILE_CONTENT', 'El contenido del archivo no coincide con su tipo declarado.');
        }

        $hash = hash_file('sha256', $path);
        if (! is_string($hash)) {
            throw new CaseDocumentException('FILE_HASH_FAILED', 'No fue posible verificar la integridad del archivo.', 500);
        }

        return ['extension' => $extension, 'mime_type' => $mime, 'size_bytes' => $size, 'sha256' => $hash, 'original_name' => $name];
    }

    private function signatureMatches(string $path, string $mime): bool
    {
        $header = file_get_contents($path, false, null, 0, 8);
        if ($mime === 'application/pdf') {
            return is_string($header) && str_starts_with($header, '%PDF-');
        }

        $image = @getimagesize($path);

        return is_array($image) && ($image['mime'] ?? null) === $mime;
    }
}
