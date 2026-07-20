<?php

namespace App\Application\Vehicles\Services;

use App\Domain\Consultas\Entities\Consultation as ConsultationEntity;
use App\Domain\Consultas\ValueObjects\ConsultationResponse;
use App\Infrastructure\Persistence\Models\Consultation;
use App\Infrastructure\Persistence\Models\Provider;
use App\Infrastructure\Persistence\Models\ProviderService;
use DateTimeImmutable;
use Throwable;

class VehicleBackfiller
{
    public function __construct(
        private readonly VehicleUpserter $vehicleUpserter
    ) {
    }

    /**
     * @return array{processed: int, errors: int}
     */
    public function run(int $chunkSize = 500): array
    {
        $providerCodes = Provider::pluck('code', 'id')->all();

        $processed = 0;
        $errors = 0;

        Consultation::query()
            ->where('success', true)
            ->whereNotNull('response_json')
            ->select([
                'id',
                'user_id',
                'provider_id',
                'criterio',
                'valor',
                'api_id',
                'services',
                'costo_credito',
                'http_status_post',
                'success',
                'error_message',
                'alerta_robo',
                'repuve_robo',
                'pgj_robo',
                'ocra_robo',
                'carfax_robo',
                'rapi_robo',
                'response_json',
                'credits_api',
                'created_at',
            ])
            ->orderBy('id')
            ->chunk($chunkSize, function ($consultations) use ($providerCodes, &$processed, &$errors) {
                foreach ($consultations as $consultation) {
                    try {
                        $providerCode = $providerCodes[$consultation->provider_id] ?? 'VINDATA';

                        $theftFlags = [
                            'repuve_robo' => (int) $consultation->repuve_robo,
                            'pgj_robo' => (int) $consultation->pgj_robo,
                            'ocra_robo' => (int) $consultation->ocra_robo,
                            'carfax_robo' => (int) $consultation->carfax_robo,
                            'rapi_robo' => (int) $consultation->rapi_robo,
                        ];

                        $response = new ConsultationResponse(
                            true,
                            $consultation->http_status_post ?? 200,
                            $consultation->error_message,
                            $consultation->response_json ?? [],
                            $consultation->api_id,
                            $theftFlags,
                            $consultation->credits_api
                        );

                        $entity = ConsultationEntity::fromResponse(
                            $consultation->user_id,
                            $consultation->provider_id,
                            $consultation->criterio,
                            $consultation->valor,
                            $consultation->services ?? [],
                            (float) $consultation->costo_credito,
                            $response,
                            new DateTimeImmutable($consultation->created_at)
                        );

                        $providerServiceId = $this->resolveProviderServiceId(
                            $consultation->provider_id,
                            $providerCode,
                            $consultation->services ?? []
                        );

                        if ($providerServiceId === null) {
                            throw new \RuntimeException('No se pudo resolver provider_service_id para la consulta');
                        }

                        $this->vehicleUpserter->upsertFromConsultation($entity, $providerCode, $providerServiceId);
                        $processed++;
                    } catch (Throwable $e) {
                        $errors++;
                        logger()->error('Vehicle backfill error', [
                            'consultation_id' => $consultation->id,
                            'message' => $e->getMessage(),
                        ]);
                    }
                }
            });

        return ['processed' => $processed, 'errors' => $errors];
    }

    private function resolveProviderServiceId(int $providerId, string $providerCode, array $services): ?int
    {
        $code = strtoupper($providerCode);

        if ($code === 'VINDATA') {
            $service = ProviderService::where('provider_id', $providerId)->where('key', 'NMVTISPlus')->first();
            return $service?->id;
        }

        if ($code === 'PLACAS') {
            $service = ProviderService::where('provider_id', $providerId)->where('key', 'Placas_Service')->first();
            return $service?->id;
        }

        $key = $services[0] ?? null;
        if ($key) {
            $service = ProviderService::where('provider_id', $providerId)->where('key', $key)->first();
            if ($service) {
                return $service->id;
            }
        }

        $service = ProviderService::where('provider_id', $providerId)->first();
        return $service?->id;
    }
}
