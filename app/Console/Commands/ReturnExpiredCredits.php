<?php

namespace App\Console\Commands;

use App\Application\Inventory\Services\ReturnExpiredCreditsService;
use Illuminate\Console\Command;

class ReturnExpiredCredits extends Command
{
    protected $signature = 'inventory:return-expired-credits';

    protected $description = 'Reintegra al inventario global los créditos de wallets cuya vigencia haya vencido';

    public function handle(ReturnExpiredCreditsService $service): int
    {
        $result = $service->run();

        $this->info("Procesados: {$result['processed']}");
        $this->info("Reintegrados: " . number_format($result['returned'], 2));
        if ($result['errors'] > 0) {
            $this->warn("Errores: {$result['errors']}");
        }

        return self::SUCCESS;
    }
}
