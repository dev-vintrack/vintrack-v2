<?php

namespace App\Application\Consultas\Services;

use App\Application\Consultas\Notifications\ConsultationNotifierInterface;
use App\Application\Credits\CommandHandlers\DebitCreditsCommandHandler;
use App\Application\Credits\Commands\DebitCreditsCommand;
use App\Application\NotificationCases\Services\ConsultationAdmissionService;
use App\Application\NotificationCases\Services\NotificationCaseCreationService;
use App\Application\Vehicles\Services\VehicleUpserter;
use App\Domain\Consultas\Entities\Consultation;
use App\Domain\Consultas\Repositories\ConsultationRepositoryInterface;
use App\Domain\Consultas\Services\ProviderAdapterRegistry;
use App\Domain\Consultas\ValueObjects\ConsultationRequest;
use App\Domain\Consultas\ValueObjects\ConsultationResponse;
use App\Domain\Credits\Repositories\WalletRepositoryInterface;
use App\Domain\Credits\ValueObjects\Money;
use App\Domain\Providers\Repositories\ProviderRepositoryInterface;
use App\Domain\Providers\Repositories\ProviderServiceRepositoryInterface;
use App\Domain\Providers\ValueObjects\ProviderId;
use App\Infrastructure\Persistence\Models\ProviderServiceSection;
use App\Infrastructure\Persistence\Models\UserProviderWallet;
use App\Models\Role;
use App\Models\User;
use DateTimeImmutable;

class ConsultationService
{
    public function __construct(
        private readonly ProviderRepositoryInterface $providerRepository,
        private readonly ProviderServiceRepositoryInterface $serviceRepository,
        private readonly ProviderAdapterRegistry $adapterRegistry,
        private readonly WalletRepositoryInterface $walletRepository,
        private readonly DebitCreditsCommandHandler $debitHandler,
        private readonly ConsultationRepositoryInterface $consultationRepository,
        private readonly ConsultationNotifierInterface $notifier,
        private readonly VehicleUpserter $vehicleUpserter,
        private readonly ?ConsultationAdmissionService $consultationAdmission = null,
        private readonly ?NotificationCaseCreationService $notificationCaseCreation = null,
    ) {}

    public function consult(
        int $userId,
        int $providerId,
        string $type,
        string $value,
        array $serviceCodes,
        ?string $requestKey = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): ConsultationResult {
        $provider = $this->providerRepository->findById(ProviderId::fromInt($providerId));
        if (! $provider || ! $provider->isEnabled()) {
            $response = new ConsultationResponse(false, 403, 'Proveedor no disponible.', [], null, $this->emptyFlags());

            return new ConsultationResult($response, $this->createUnsavedConsultation($userId, 0, $type, $value, $serviceCodes, $response));
        }

        $enabledServices = $this->serviceRepository->findEnabledByProviderId($provider->id()->value());
        $enabledServiceCodes = array_map(fn ($s) => $s->serviceCode(), $enabledServices);
        $requestedServiceCodes = array_values(array_intersect($serviceCodes, $enabledServiceCodes));
        if (empty($requestedServiceCodes)) {
            $response = new ConsultationResponse(false, 422, 'Debe seleccionar al menos un servicio habilitado.', [], null, $this->emptyFlags());

            return new ConsultationResult($response, $this->createUnsavedConsultation($userId, $provider->id()->value(), $type, $value, $serviceCodes, $response));
        }

        $debitServiceCode = $requestedServiceCodes[0];
        $providerService = $this->serviceRepository->findByServiceCode($debitServiceCode);
        if (! $providerService) {
            $response = new ConsultationResponse(false, 500, 'Servicio no encontrado para el proveedor.', [], null, $this->emptyFlags());

            return new ConsultationResult($response, $this->createUnsavedConsultation($userId, $provider->id()->value(), $type, $value, $requestedServiceCodes, $response));
        }

        $providerServiceId = $providerService->id();
        $adapterCode = $provider->adapterCode();
        $requestKey ??= 'consult-'.bin2hex(random_bytes(16));
        $admission = $this->consultationAdmission ?? app(ConsultationAdmissionService::class);
        $caseCreation = $this->notificationCaseCreation ?? app(NotificationCaseCreationService::class);
        $reservationId = $admission->reserve($userId, $requestKey, $ipAddress, $userAgent);
        UserProviderWallet::syncExpiredStatuses();
        $wallet = $this->walletRepository->findByUserAndServiceOrCreate($userId, $providerServiceId);
        $cost = $providerService->creditCost()->amount();
        if (! $wallet->isValidAt(new DateTimeImmutable)) {
            $admission->release($reservationId);
            $response = new ConsultationResponse(false, 402, 'Los créditos para este servicio han expirado.', [], null, $this->emptyFlags());

            return new ConsultationResult($response, $this->createUnsavedConsultation($userId, $provider->id()->value(), $type, $value, [$debitServiceCode], $response, $cost, $providerServiceId));
        }
        if ($cost > 0 && ! $wallet->balance()->isGreaterThanOrEqual(Money::fromFloat($cost))) {
            $admission->release($reservationId);
            $response = new ConsultationResponse(false, 402, 'Saldo insuficiente de créditos.', [], null, $this->emptyFlags());

            return new ConsultationResult($response, $this->createUnsavedConsultation($userId, $provider->id()->value(), $type, $value, [$debitServiceCode], $response, $cost, $providerServiceId));
        }

        $adapterServices = $this->buildAdapterServices(
            strtolower($adapterCode),
            $providerServiceId,
            $requestedServiceCodes,
            $userId
        );

        $request = new ConsultationRequest($userId, $adapterCode, $value, $type, $adapterServices);
        $adapter = $this->adapterRegistry->resolve($adapterCode);
        try {
            $response = $adapter->consult($request);
        } catch (\Throwable $exception) {
            $admission->release($reservationId);
            throw $exception;
        }

        if ($response->success() && $cost > 0) {
            $correlationId = 'consult-'.$adapterCode.'-'.$userId.'-'.time().'-'.bin2hex(random_bytes(4));
            $debitCommand = new DebitCreditsCommand(
                $userId,
                $providerServiceId,
                $cost,
                'Consulta '.$adapterCode.' '.strtoupper($type).' '.$value,
                $correlationId,
                null
            );
            try {
                $this->debitHandler->handle($debitCommand);
            } catch (\Throwable $exception) {
                $admission->release($reservationId);
                throw $exception;
            }
        }

        $consultation = Consultation::fromResponse(
            $userId,
            $provider->id()->value(),
            $providerServiceId,
            $type,
            $value,
            [$debitServiceCode],
            $cost,
            $response,
            new DateTimeImmutable
        );
        $savedConsultation = $this->consultationRepository->save($consultation);

        if ($response->success()) {
            $this->vehicleUpserter->upsertFromConsultation($savedConsultation, $adapterCode, $providerServiceId);
            $this->dispatchNotifications($userId, $providerServiceId, $adapterCode, $savedConsultation);
        }

        if ($response->success() && $savedConsultation->alertaRobo()) {
            $caseCreation->createOrReuse($savedConsultation, $adapterCode, $reservationId);
        } else {
            $admission->consume($reservationId);
        }

        return new ConsultationResult($response, $savedConsultation);
    }

    private function buildAdapterServices(string $adapterCode, int $providerServiceId, array $requestedServices, int $userId): array
    {
        if ($adapterCode !== 'placas') {
            return $requestedServices;
        }

        $user = User::find($userId);
        $roleName = $user?->rol ?? 'cliente_registrado';
        $roleId = Role::idForName($roleName);

        return ProviderServiceSection::where('provider_service_id', $providerServiceId)
            ->where('status', true)
            ->when($roleId, function ($query, $roleId) {
                $query->whereHas('roleSettings', function ($query) use ($roleId) {
                    $query->where('id_rol', $roleId)->where('status', true);
                });
            })
            ->orderBy('section_code')
            ->pluck('section_code')
            ->all();
    }

    private function dispatchNotifications(
        int $userId,
        int $providerServiceId,
        string $adapterCode,
        Consultation $consultation
    ): void {
        if (strtolower($adapterCode) === 'placas' && $consultation->alertaRobo()) {
            $this->notifier->sendPlacasTheftAlert($userId, $providerServiceId, $consultation);
        }
    }

    private function createUnsavedConsultation(
        int $userId,
        int $providerId,
        string $type,
        string $value,
        array $services,
        ConsultationResponse $response,
        float $cost = 0,
        int $providerServiceId = 0
    ): Consultation {
        return Consultation::fromResponse(
            $userId,
            $providerId,
            $providerServiceId,
            $type,
            $value,
            $services,
            $cost,
            $response,
            new DateTimeImmutable
        );
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
