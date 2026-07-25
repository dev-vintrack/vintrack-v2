<?php

namespace App\Application\Inventory\Services;

use App\Domain\Credits\Repositories\LedgerRepositoryInterface;
use App\Domain\Credits\Repositories\WalletRepositoryInterface;
use App\Domain\Credits\ValueObjects\Amount;
use App\Domain\Credits\ValueObjects\CorrelationId;
use App\Domain\Credits\ValueObjects\Money;
use App\Infrastructure\Persistence\Models\ProviderService;
use App\Infrastructure\Persistence\Models\UserPackage;
use App\Infrastructure\Persistence\Models\UserProviderWallet;
use App\Mail\ExpiredCreditsMail;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class ReturnExpiredCreditsService
{
    public function __construct(
        private readonly WalletRepositoryInterface $walletRepository,
        private readonly LedgerRepositoryInterface $ledgerRepository,
        private readonly InventoryMovementService $inventoryService
    ) {
    }

    /**
     * @return array{processed: int, errors: int, returned: float, candidates: int, errorDetails: array}
     */
    public function run(): array
    {
        UserPackage::syncExpiredStatuses();
        UserProviderWallet::syncExpiredStatuses();

        $processed = 0;
        $errors = 0;
        $returned = 0.0;
        $errorDetails = [];

        $wallets = UserProviderWallet::with('user', 'service')
            ->whereNotNull('validity_end')
            ->where('validity_end', '<=', now())
            ->where('balance', '>', 0)
            ->get();

        foreach ($wallets as $walletModel) {
            try {
                $balance = (float) $walletModel->balance;

                DB::transaction(function () use ($walletModel, $balance) {
                    $wallet = $this->walletRepository->findByUserAndService(
                        $walletModel->user_id,
                        $walletModel->provider_service_id
                    );

                    if (! $wallet) {
                        throw new RuntimeException('Wallet no encontrada.');
                    }

                    $wallet->debit(Money::fromFloat($balance));
                    $wallet->setValidityEnd(null);
                    $this->walletRepository->save($wallet);

                    $this->inventoryService->returnExpired(
                        $walletModel->provider_service_id,
                        $balance,
                        $walletModel->id
                    );

                    UserProviderWallet::where('id', $walletModel->id)->update(['status' => 'expired']);

                    $ledgerEntry = new \App\Domain\Credits\Entities\LedgerEntry(
                        null,
                        $wallet->id()->value(),
                        $walletModel->provider_service_id,
                        Amount::fromFloat(-$balance),
                        'Reintegración por vencimiento de vigencia',
                        [
                            'wallet_id' => $walletModel->id,
                            'automatic' => true,
                        ],
                        CorrelationId::fromString('expiry-' . $walletModel->id . '-' . time()),
                        new DateTimeImmutable()
                    );
                    $this->ledgerRepository->save($ledgerEntry);
                });

                $service = $walletModel->service ?? ProviderService::find($walletModel->provider_service_id);

                if ($walletModel->user && $service) {
                    try {
                        Mail::to($walletModel->user->email)
                            ->send(new ExpiredCreditsMail($walletModel, $service, $balance));
                    } catch (Throwable $e) {
                        logger()->warning('No se pudo enviar correo de créditos vencidos', [
                            'wallet_id' => $walletModel->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                $processed++;
                $returned += $balance;
            } catch (Throwable $e) {
                $errors++;
                $errorDetails[] = [
                    'wallet_id' => $walletModel->id,
                    'message' => $e->getMessage(),
                    'recommendation' => $this->recommendationFor($e),
                ];

                logger()->error('Error reintegrando créditos vencidos', [
                    'wallet_id' => $walletModel->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return [
            'processed' => $processed,
            'errors' => $errors,
            'returned' => $returned,
            'candidates' => $wallets->count(),
            'errorDetails' => $errorDetails,
        ];
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

        if (str_contains($message, 'servicio no encontrado')) {
            return 'Verifica que el provider_service_id del wallet corresponda a un servicio activo existente.';
        }

        if (str_contains($message, 'insufficient balance')) {
            return 'El wallet no tiene saldo suficiente; probablemente ya fue procesado o fue debitado manualmente.';
        }

        if (str_contains($message, 'duplicate')) {
            return 'Se detectó un movimiento duplicado; verifica el ledger y evita reejecutar el proceso.';
        }

        return 'Revisa los logs de Laravel y la base de datos; si persiste, ejecuta php artisan migrate o el script SQL correspondiente.';
    }
}
