<?php

namespace App\Infrastructure\Persistence\Eloquent\Consultas;

use App\Domain\Consultas\Entities\Consultation;
use App\Domain\Consultas\Repositories\ConsultationRepositoryInterface;
use App\Infrastructure\Persistence\Models\Consultation as ConsultationModel;
use DateTimeImmutable;

class ConsultationRepository implements ConsultationRepositoryInterface
{
    public function save(Consultation $consultation): void
    {
        $model = ConsultationModel::updateOrCreate(
            ['id' => $consultation->id()],
            [
                'user_id' => $consultation->userId(),
                'provider_id' => $consultation->providerId(),
                'criterio' => $consultation->criterio(),
                'valor' => $consultation->valor(),
                'api_id' => $consultation->apiId(),
                'services' => $consultation->services(),
                'costo_credito' => $consultation->costoCredito(),
                'http_status_post' => $consultation->responseJson() ? 200 : null,
                'http_status_get' => $consultation->responseJson() ? 200 : null,
                'success' => $consultation->success(),
                'error_message' => $consultation->errorMessage(),
                'alerta_robo' => $consultation->alertaRobo(),
                'repuve_robo' => $consultation->theftFlags()['repuve_robo'] ?? 0,
                'pgj_robo' => $consultation->theftFlags()['pgj_robo'] ?? 0,
                'ocra_robo' => $consultation->theftFlags()['ocra_robo'] ?? 0,
                'carfax_robo' => $consultation->theftFlags()['carfax_robo'] ?? 0,
                'rapi_robo' => $consultation->theftFlags()['rapi_robo'] ?? 0,
                'response_json' => $consultation->responseJson(),
                'credits_api' => $consultation->creditsApi(),
                'created_at' => $consultation->createdAt()->format('Y-m-d H:i:s'),
            ]
        );
    }

    public function findById(int $id): ?Consultation
    {
        $model = ConsultationModel::find($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function findByUserId(int $userId, int $limit = 50): array
    {
        return ConsultationModel::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn (ConsultationModel $model) => $this->toEntity($model))
            ->all();
    }

    private function toEntity(ConsultationModel $model): Consultation
    {
        return new Consultation(
            $model->id,
            $model->user_id,
            $model->provider_id,
            $model->criterio,
            $model->valor,
            $model->api_id,
            $model->services ?? [],
            (float) $model->costo_credito,
            $model->http_status_post,
            $model->http_status_get,
            $model->success,
            $model->error_message,
            $model->alerta_robo,
            [
                'repuve_robo' => $model->repuve_robo ? 1 : 0,
                'pgj_robo' => $model->pgj_robo ? 1 : 0,
                'ocra_robo' => $model->ocra_robo ? 1 : 0,
                'carfax_robo' => $model->carfax_robo ? 1 : 0,
                'rapi_robo' => $model->rapi_robo ? 1 : 0,
            ],
            $model->response_json ?? [],
            $model->credits_api,
            new DateTimeImmutable($model->created_at)
        );
    }
}
