<?php

namespace App\Application\Credits\CommandHandlers;

use App\Application\Credits\Commands\AddCreditsCommand;
use App\Application\Inventory\Services\InventoryMovementService;
use App\Domain\Credits\Entities\LedgerEntry;
use App\Domain\Credits\Repositories\LedgerRepositoryInterface;
use App\Domain\Credits\Repositories\WalletRepositoryInterface;
use App\Domain\Credits\ValueObjects\Amount;
use App\Domain\Credits\ValueObjects\CorrelationId;
use App\Domain\Credits\ValueObjects\Money;
use App\Infrastructure\Persistence\Models\ProviderService;
use App\Infrastructure\Persistence\Models\UserProviderWallet;
use DateTimeImmutable;
use RuntimeException;

class AddCreditsCommandHandler
{
    public function __construct(
        private readonly WalletRepositoryInterface $walletRepository,
        private readonly LedgerRepositoryInterface $ledgerRepository,
        private readonly InventoryMovementService $inventoryService
    ) {
    }

    public function handle(AddCreditsCommand $command): void
    {
        if ($command->amount <= 0) {
            throw new RuntimeException('Amount to add must be positive.');
        }

        $existingEntry = $this->ledgerRepository->findByCorrelationId(
            CorrelationId::fromString($command->correlationId)
        );
        if ($existingEntry) {
            throw new RuntimeException('Duplicate credit add detected.');
        }

        $service = ProviderService::find($command->providerServiceId);
        if (! $service) {
            throw new RuntimeException('Servicio no encontrado.');
        }

        $amount = Money::fromFloat($command->amount);

        if ((float) $service->available_credits < $command->amount) {
            throw new RuntimeException('Inventario insuficiente para asignar estos créditos.');
        }

        $wallet = $this->walletRepository->findByUserAndServiceOrCreate(
            $command->userId,
            $command->providerServiceId
        );

        $wallet->credit($amount);

        if ($command->validityEnd !== null) {
            $currentValidityEnd = $wallet->validityEnd();

            if ($currentValidityEnd === null || $command->validityEnd > $currentValidityEnd) {
                if ($command->validityStart !== null) {
                    $wallet->setValidityStart($command->validityStart);
                }
                $wallet->setValidityEnd($command->validityEnd);
            }
        }

        $this->walletRepository->save($wallet);
        UserProviderWallet::where('id', $wallet->id()?->value())->update(['status' => 'active']);

        $this->inventoryService->sale(
            $command->providerServiceId,
            $command->amount,
            UserProviderWallet::class,
            $wallet->id()->value(),
            $command->adminId,
            'Venta de créditos a cliente'
        );

        $ledgerEntry = new LedgerEntry(
            null,
            $wallet->id()->value(),
            $command->providerServiceId,
            Amount::fromFloat($command->amount),
            $command->reason,
            [
                'admin_id' => $command->adminId,
                'validity_end' => $command->validityEnd?->format('c'),
            ],
            CorrelationId::fromString($command->correlationId),
            new DateTimeImmutable()
        );

        $this->ledgerRepository->save($ledgerEntry);
    }
}
