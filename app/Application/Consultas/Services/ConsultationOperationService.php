<?php

namespace App\Application\Consultas\Services;

use App\Application\Consultas\Exceptions\ConsultationOperationException;
use App\Domain\Consultas\ValueObjects\ConsultationResponse;
use App\Domain\Consultas\ValueObjects\ProviderResultAssessment;
use Illuminate\Support\Facades\DB;

final class ConsultationOperationService
{
    public function claim(int $userId, int $providerServiceId, string $criterion, string $value, array $services, string $key): ConsultationOperationClaim
    {
        $keyHash = hash('sha256', $key);
        $fingerprint = hash('sha256', json_encode([
            'provider_service_id' => $providerServiceId,
            'criterion' => strtolower(trim($criterion)),
            'value' => strtoupper(trim($value)),
            'services' => array_values($services),
        ], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($userId, $providerServiceId, $keyHash, $fingerprint) {
            $inserted = DB::table('consultation_operations')->insertOrIgnore([
                'user_id' => $userId,
                'provider_service_id' => $providerServiceId,
                'idempotency_key_hash' => $keyHash,
                'request_fingerprint' => $fingerprint,
                'status' => 'IN_PROGRESS',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $operation = DB::table('consultation_operations')
                ->where('user_id', $userId)
                ->where('idempotency_key_hash', $keyHash)
                ->lockForUpdate()
                ->first();

            if (! $operation || ! hash_equals($operation->request_fingerprint, $fingerprint)) {
                throw new ConsultationOperationException('IDEMPOTENCY_KEY_REUSED', 'La clave de idempotencia ya pertenece a otra solicitud.');
            }

            if ($inserted === 1) {
                return new ConsultationOperationClaim((int) $operation->id, false);
            }

            if ($operation->status === 'COMPLETED') {
                return new ConsultationOperationClaim(
                    (int) $operation->id,
                    true,
                    (int) $operation->consultation_id,
                    json_decode($operation->response_snapshot, true, 512, JSON_THROW_ON_ERROR),
                );
            }

            if ($operation->status === 'FAILED_RETRYABLE') {
                DB::table('consultation_operations')->where('id', $operation->id)->update([
                    'status' => 'IN_PROGRESS', 'failure_code' => null, 'updated_at' => now(),
                ]);

                return new ConsultationOperationClaim((int) $operation->id, false);
            }

            if ($operation->status === 'FAILED_AMBIGUOUS') {
                throw new ConsultationOperationException('IDEMPOTENCY_AMBIGUOUS', 'La operación requiere revisión y no se reinvocará automáticamente.');
            }

            throw new ConsultationOperationException('IDEMPOTENCY_IN_PROGRESS', 'La consulta con esta clave está en proceso.');
        }, 3);
    }

    public function providerStarted(int $operationId): void
    {
        DB::table('consultation_operations')->where('id', $operationId)->update([
            'provider_started_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function fail(int $operationId, string $code, bool $ambiguous): void
    {
        DB::table('consultation_operations')->where('id', $operationId)->update([
            'status' => $ambiguous ? 'FAILED_AMBIGUOUS' : 'FAILED_RETRYABLE',
            'failure_code' => $code,
            'updated_at' => now(),
        ]);
    }

    public function complete(int $operationId, int $consultationId, ConsultationResponse $response): void
    {
        $snapshot = json_encode([
            'success' => $response->success(), 'http_status' => $response->httpStatus(),
            'error_message' => $response->errorMessage(), 'data' => $response->data(),
            'api_id' => $response->apiId(), 'theft_flags' => $response->theftFlags(),
            'credits_api' => $response->creditsApi(),
            'assessment' => $response->assessment()?->toArray(),
        ], JSON_THROW_ON_ERROR);

        DB::table('consultation_operations')->where('id', $operationId)->update([
            'status' => 'COMPLETED', 'consultation_id' => $consultationId,
            'response_snapshot' => $snapshot, 'completed_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function responseFromSnapshot(array $snapshot): ConsultationResponse
    {
        return new ConsultationResponse(
            $snapshot['success'], $snapshot['http_status'], $snapshot['error_message'],
            $snapshot['data'], $snapshot['api_id'], $snapshot['theft_flags'], $snapshot['credits_api'],
            isset($snapshot['assessment']) && is_array($snapshot['assessment'])
                ? new ProviderResultAssessment(
                    $snapshot['assessment']['service_code'],
                    $snapshot['assessment']['classification'],
                    $snapshot['assessment']['predicates'] ?? [],
                    $snapshot['assessment']['evidence_paths'] ?? [],
                )
                : null,
        );
    }
}
