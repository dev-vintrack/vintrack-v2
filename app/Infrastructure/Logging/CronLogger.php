<?php

namespace App\Infrastructure\Logging;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

class CronLogger
{
    private string $path;

    public function __construct(?string $path = null)
    {
        $this->path = $path ?? base_path('routes/vintrack_cron.log');
    }

    public function info(string $message): void
    {
        $this->write('INFO', $message);
    }

    public function warning(string $message): void
    {
        $this->write('WARNING', $message);
    }

    public function error(string $message): void
    {
        $this->write('ERROR', $message);
    }

    private function write(string $level, string $message): void
    {
        $line = '[' . now()->format('Y-m-d H:i:s') . '] [' . $level . '] ' . $message . PHP_EOL;

        $directory = dirname($this->path);
        if (! is_dir($directory) && ! @mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('No se pudo crear el directorio del log: ' . $directory);
        }

        if (file_put_contents($this->path, $line, FILE_APPEND | LOCK_EX) === false) {
            throw new RuntimeException('No se pudo escribir en el archivo de log: ' . $this->path);
        }
    }
}
