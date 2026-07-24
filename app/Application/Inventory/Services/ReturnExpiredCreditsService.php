<?php

namespace App\Application\Inventory\Services;

use App\Domain\Credits\Repositories\LedgerRepositoryInterface;
use App\Domain\Credits\Repositories\WalletRepositoryInterface;
use App\Domain\Credits\ValueObjects\Amount;
use App\Domain\Credits\ValueObjects\CorrelationId;
use App\Domain\Credits\ValueObjects\Money;
use App\Infrastructure\Persistence\Models\ProviderService;
use App\Infrastructure\Persistence\Models\UserProviderWallet;
use App\Mail\ExpiredCreditsMail;
use DateTimeImmutable;
use Illuminate\Support\Facades\Mail;
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
     * @return array{processed: int, errors: int, returned: float}
     */
    public function run(): array
    {
        UserProviderWallet::syncExpiredStatuses();

        $processed = 0;
        $errors = 0;
        $returned = 0.0;

        $wallets = UserProviderWallet::with('user', 'service')
            ->where('validity_end', '<', now())
            ->where('balance', '>', 0)
            ->get();

        foreach ($wallets as $walletModel) {
            try {
                $balance = (float) $walletModel->balance;

                $wallet = $this->walletRepository->findByUserAndService(
                    $walletModel->user_id,
                    $walletModel->provider_service_id
                );

                if (! $wallet) {
                    throw new \RuntimeException('Wallet no encontrada.');
                }

                $wallet->debit(Money::fromFloat($balance));
                $wallet->setValidityEnd(null);
                $this->walletRepository->save($wallet);
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

                $this->inventoryService->returnExpired(
                    $walletModel->provider_service_id,
                    $balance,
                    $walletModel->id
                );

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
        ];
    }
}
