<?php

namespace App\Console\Commands;

use App\Application\Inventory\Services\ReturnExpiredCreditsService;
use App\Infrastructure\Logging\CronLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ReturnExpiredCredits extends Command
{
    protected $signature = 'inventory:return-expired-credits';

    protected $description = 'Reintegra al inventario global los créditos de wallets cuya vigencia haya vencido';

    public function handle(ReturnExpiredCreditsService $service): int
    {
        $log = new CronLogger();

        $log->info('=== Inicio de ejecución del cron de reintegro de créditos ===');

        if (! Schema::hasColumn('user_provider_wallets', 'status')) {
            $message = 'La tabla user_provider_wallets no tiene la columna "status". Ejecuta el script deploy/2026-07-23_package_wallet_statuses.sql en PHPMyAdmin o corre php artisan migrate.';
            $log->error($message);
            $this->error($message);
            return self::FAILURE;
        }

        if (! Schema::hasColumn('user_packages', 'status')) {
            $message = 'La tabla user_packages no tiene la columna "status". Ejecuta el script deploy/2026-07-23_package_wallet_statuses.sql en PHPMyAdmin o corre php artisan migrate.';
            $log->error($message);
            $this->error($message);
            return self::FAILURE;
        }

        try {
            $result = $service->run();

            $log->info("Avisos de próximo vencimiento evaluados: {$result['expiringCandidates']}");
            $log->info("Candidatos encontrados: {$result['candidates']}");
            $log->info("Procesados: {$result['processed']}");
            $log->info("Reintegrados: " . number_format($result['returned'], 2));
            $log->info("Errores: {$result['errors']}");

            if (! empty($result['errorDetails'])) {
                foreach ($result['errorDetails'] as $error) {
                    $log->error("Wallet {$error['wallet_id']}: {$error['message']} Solución recomendada: {$error['recommendation']}");
                }
            }

            $this->info("Avisos de próximo vencimiento evaluados: {$result['expiringCandidates']}");
            $this->info("Procesados: {$result['processed']}");
            $this->info("Reintegrados: " . number_format($result['returned'], 2));
            if ($result['errors'] > 0) {
                $this->warn("Errores: {$result['errors']}");
            }

            return self::SUCCESS;
        } catch (Throwable $e) {
            $description = $e->getMessage();
            $recommendation = $this->recommendationFor($e);

            $log->error("Error en la ejecución del cron: {$description}. Solución recomendada: {$recommendation}");
            $this->error("Error en la ejecución del cron: {$description}");

            return self::FAILURE;
        } finally {
            $log->info('=== Fin de ejecución del cron ===');
        }
    }

    private function recommendationFor(Throwable $e): string
    {
        $message = strtolower($e->getMessage());

        if (str_contains($message, 'inventario negativo')) {
            return 'Revisa el inventario global del servicio y ajusta manualmente si es necesario antes de reintentar.';
        }

        if (str_contains($message, 'wallet no encontrada')) {
            return 'Verifica que el wallet no haya sido eliminado y sincroniza los estados desde el panel de administración.';
        }

        if (str_contains($message, 'insufficient balance')) {
            return 'El wallet no tiene saldo suficiente; probablemente ya fue procesado o fue debitado manualmente.';
        }

        if (str_contains($message, 'column') || str_contains($message, 'unknown')) {
            return 'Verifica que las migraciones o scripts SQL estén aplicados correctamente en la base de datos.';
        }

        return 'Revisa los logs de Laravel y la base de datos; si persiste, ejecuta php artisan migrate o el script SQL correspondiente.';
    }
}
