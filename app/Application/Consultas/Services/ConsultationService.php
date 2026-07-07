<?php

namespace App\Application\Consultas\Services;

use App\Application\Credits\CommandHandlers\DebitCreditsCommandHandler;
use App\Application\Credits\Commands\DebitCreditsCommand;
use App\Domain\Consultas\Entities\Consultation;
use App\Domain\Consultas\Repositories\ConsultationRepositoryInterface;
use App\Domain\Consultas\Services\ProviderAdapterRegistry;
use App\Domain\Consultas\ValueObjects\ConsultationRequest;
use App\Domain\Consultas\ValueObjects\ConsultationResponse;
use App\Domain\Credits\Repositories\WalletRepositoryInterface;
use App\Domain\Credits\ValueObjects\Money;
use App\Domain\Providers\Repositories\ProviderRepositoryInterface;
use App\Domain\Providers\Repositories\ProviderServiceRepositoryInterface;
use App\Domain\Providers\ValueObjects\ProviderCode;
use DateTimeImmutable;
use RuntimeException;

class ConsultationService
{
    public function __construct(
        private readonly ProviderRepositoryInterface $providerRepository,
        private readonly ProviderServiceRepositoryInterface $serviceRepository,
        private readonly ProviderAdapterRegistry $adapterRegistry,
        private readonly WalletRepositoryInterface $walletRepository,
        private readonly DebitCreditsCommandHandler $debitHandler,
        private readonly ConsultationRepositoryInterface $consultationRepository
    ) {
    }

    public function consult(
        int $userId,
        string $providerCode,
        string $type,
        string $value,
        array $services
    ): ConsultationResponse {
        $providerCodeVo = ProviderCode::fromString($providerCode);
        $provider = $this->providerRepository->findByCode($providerCodeVo);
        if (!$provider || !$provider->isEnabled()) {
            return new ConsultationResponse(false, 403, 'Proveedor no disponible.', [], null, $this->emptyFlags());
        }

        $enabledServices = $this->serviceRepository->findEnabledByProviderId($provider->id()->value());
        $enabledKeys = array_map(fn ($s) => $s->key(), $enabledServices);
        $requestedServices = array_values(array_intersect($services, $enabledKeys));
        if (empty($requestedServices)) {
            return new ConsultationResponse(false, 422, 'Debe seleccionar al menos un servicio habilitado.', [], null, $this->emptyFlags());
        }

        $wallet = $this->walletRepository->findByUserAndProviderOrCreate($userId, $provider->id()->value());
        $cost = $provider->creditCost();
        if ($cost > 0 && !$wallet->balance()->isGreaterThanOrEqual(Money::fromFloat($cost))) {
            return new ConsultationResponse(false, 402, 'Saldo insuficiente de créditos.', [], null, $this->emptyFlags());
        }

        $request = new ConsultationRequest($userId, $providerCode, $value, $type, $requestedServices);
        $adapter = $this->adapterRegistry->resolve($providerCode);
        $response = $adapter->consult($request);

        if ($response->success() && $cost > 0) {
            $correlationId = 'consult-' . $providerCode . '-' . $userId . '-' . time() . '-' . bin2hex(random_bytes(4));
            $debitCommand = new DebitCreditsCommand(
                $userId,
                $provider->id()->value(),
                $cost,
                'Consulta ' . strtoupper($providerCode) . ' ' . strtoupper($type) . ' ' . $value,
                $correlationId
            );
            $this->debitHandler->handle($debitCommand);
        }

        $consultation = Consultation::fromResponse(
            $userId,
            $provider->id()->value(),
            $type,
            $value,
            $requestedServices,
            $cost,
            $response,
            new DateTimeImmutable()
        );
        $this->consultationRepository->save($consultation);

        return $response;
    }

    private function emptyFlags(): array
    {
        return [
            'repuve_robo' => 0,
            'pgj_robo' => 0,
            'ocra_robo' => 0,
            'carfax_robo' => 0,
            'rapi_robo' => 0,
        ];
    }
}
