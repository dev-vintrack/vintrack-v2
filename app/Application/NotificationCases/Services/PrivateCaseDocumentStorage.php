<?php

namespace App\Application\NotificationCases\Services;

use App\Application\NotificationCases\Exceptions\CaseDocumentException;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivateCaseDocumentStorage
{
    public const DISK = 'local';

    public function __construct(private readonly FilesystemManager $filesystems) {}

    public function store(UploadedFile $file, string $extension): string
    {
        $key = 'notification-case-evidence/'.Str::uuid()->toString().'.'.$extension;
        $stream = fopen($file->getRealPath(), 'rb');
        $stored = is_resource($stream) && $this->filesystems->disk(self::DISK)->writeStream($key, $stream);
        if (is_resource($stream)) {
            fclose($stream);
        }
        if (! $stored) {
            throw new CaseDocumentException('PRIVATE_STORAGE_FAILED', 'No fue posible almacenar el archivo.', 500);
        }

        return $key;
    }

    public function delete(string $key): void
    {
        $this->filesystems->disk(self::DISK)->delete($key);
    }

    public function exists(string $key): bool
    {
        return $this->filesystems->disk(self::DISK)->exists($key);
    }

    /** @return resource|false */
    public function readStream(string $key)
    {
        return $this->filesystems->disk(self::DISK)->readStream($key);
    }

    public function download(string $key, string $safeName, string $mime): StreamedResponse
    {
        return $this->filesystems->disk(self::DISK)->download($key, $safeName, [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }
}
