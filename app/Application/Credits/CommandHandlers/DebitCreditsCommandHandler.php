<?php

namespace App\Application\Credits\CommandHandlers;

use App\Application\Credits\Commands\DebitCreditsCommand;
use App\Application\Notifications\Services\CustomerMailNotificationService;
use App\Domain\Credits\Entities\LedgerEntry;
use App\Domain\Credits\Repositories\LedgerRepositoryInterface;
use App\Domain\Credits\Repositories\WalletRepositoryInterface;
use App\Domain\Credits\ValueObjects\Amount;
use App\Domain\Credits\ValueObjects\CorrelationId;
use App\Domain\Credits\ValueObjects\Money;
use DateTimeImmutable;
use RuntimeException;

class DebitCreditsCommandHandler
{
    public function __construct(
        private readonly WalletRepositoryInterface $walletRepository,
        private readonly LedgerRepositoryInterface $ledgerRepository,
        private readonly CustomerMailNotificationService $notifications
    ) {
    }

    public function handle(DebitCreditsCommand $command): void
    {
        if ($command->amount <= 0) {
            throw new RuntimeException('Amount to debit must be positive.');
        }

        $existingEntry = $this->ledgerRepository->findByCorrelationId(
            CorrelationId::fromString($command->correlationId)
        );
        if ($existingEntry) {
            throw new RuntimeException('Duplicate debit detected.');
        }

        $wallet = $this->walletRepository->findByUserAndService($command->userId, $command->providerServiceId);
        if (!$wallet) {
            throw new RuntimeException('Wallet not found.');
        }

        $amount = Money::fromFloat($command->amount);
        $wallet->debit($amount);
        $this->walletRepository->save($wallet);

        $ledgerEntry = new LedgerEntry(
            null,
            $wallet->id()->value(),
            $command->providerServiceId,
            Amount::fromFloat(-$command->amount),
            $command->reason,
            [
                'consultation_id' => $command->consultationId,
            ],
            CorrelationId::fromString($command->correlationId),
            new DateTimeImmutable()
        );

        $this->ledgerRepository->save($ledgerEntry);

        $this->notifications->notifyBalanceAfterDebit(
            $command->userId,
            $command->providerServiceId,
            $wallet->balance()->amount(),
            $command->correlationId
        );
    }
}
