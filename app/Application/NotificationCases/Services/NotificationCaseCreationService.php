<?php

namespace App\Application\NotificationCases\Services;

use App\Application\Vehicles\Services\VehicleDataExtractor;
use App\Domain\Consultas\Entities\Consultation as ConsultationEntity;
use App\Domain\NotificationCases\Enums\CanonicalCriterion;
use App\Domain\NotificationCases\Enums\NotificationCaseStatus;
use App\Domain\NotificationCases\Services\NotificationCaseTemporalPolicy;
use App\Domain\NotificationCases\Services\TextNormalizer;
use App\Domain\NotificationCases\ValueObjects\CaseNumber;
use App\Domain\NotificationCases\ValueObjects\Vin;
use App\Infrastructure\Persistence\Models\NotificationCase;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class NotificationCaseCreationService
{
    public function __construct(
        private readonly NotificationCaseSettings $settings,
        private readonly ConsultationVinMapper $vinMapper,
        private readonly NotificationCaseAuditService $audit,
        private readonly NotificationCaseOutboxService $outbox,
        private readonly TextNormalizer $normalizer,
    ) {}

    public function createOrReuse(ConsultationEntity $consultation, string $adapterCode, int $reservationId): ?NotificationCase
    {
        if (! $consultation->success() || ! $consultation->alertaRobo() || ! $consultation->id()) {
            return null;
        }

        $mapped = $this->vinMapper->map($consultation->criterio(), $consultation->valor(), $adapterCode, $consultation->responseJson());
        $criterion = CanonicalCriterion::fromConsultation($consultation->criterio());
        $sourceKey = hash('sha256', $consultation->providerServiceId().'|PLATE|'.strtoupper(preg_replace('/[^A-Z0-9]/i', '', $consultation->valor()) ?? ''));

        return DB::transaction(function () use ($consultation, $adapterCode, $reservationId, $mapped, $criterion, $sourceKey) {
            DB::table('notification_case_user_guards')->insertOrIgnore([
                'user_id' => $consultation->userId(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('notification_case_user_guards')->where('user_id', $consultation->userId())->lockForUpdate()->first();

            $existingByConsultation = NotificationCase::where('consultation_id', $consultation->id())->first();
            if ($existingByConsultation) {
                $this->consumeReservation($reservationId);

                return $existingByConsultation;
            }

            if ($mapped instanceof Vin) {
                DB::table('notification_case_vin_guards')->insertOrIgnore(['vin_key' => $mapped->value(), 'created_at' => now(), 'updated_at' => now()]);
                DB::table('notification_case_vin_guards')->where('vin_key', $mapped->value())->lockForUpdate()->first();
                $applicable = $this->findApplicableByVin($mapped->value(), CarbonImmutable::instance($consultation->createdAt()));
            } else {
                DB::table('notification_case_source_guards')->insertOrIgnore(['source_key' => $sourceKey, 'created_at' => now(), 'updated_at' => now()]);
                DB::table('notification_case_source_guards')->where('source_key', $sourceKey)->lockForUpdate()->first();
                $plateKey = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $consultation->valor()) ?? '');
                $applicable = NotificationCase::query()
                    ->join('consultations', 'consultations.id', '=', 'notification_cases.consultation_id')
                    ->where('consultations.provider_service_id', $consultation->providerServiceId())
                    ->whereRaw('LOWER(consultations.criterio) = ?', ['placa'])
                    ->whereIn('notification_cases.status', array_map(fn ($s) => $s->value, array_filter(NotificationCaseStatus::cases(), fn ($s) => $s->isPending())))
                    ->select('notification_cases.*', 'consultations.valor as source_value')->lockForUpdate()->get()
                    ->first(fn ($candidate) => strtoupper(preg_replace('/[^A-Z0-9]/i', '', $candidate->source_value) ?? '') === $plateKey);
            }

            if ($applicable) {
                $this->consumeReservation($reservationId);

                return $applicable;
            }

            $now = CarbonImmutable::now($this->settings->timezone());
            $policy = new NotificationCaseTemporalPolicy($this->settings->timezone(), $this->settings->deadlineDays(), $this->settings->maxOpenDays(), $this->settings->reuseDays());
            $previous = $mapped instanceof Vin
                ? NotificationCase::where('vin_key', $mapped->value())->orderByDesc('opened_at')->orderByDesc('id')->lockForUpdate()->first()
                : null;
            $caseNumber = $this->nextCaseNumber((int) $now->format('Y'));
            $vehicle = VehicleDataExtractor::extract($consultation->responseJson(), $adapterCode);

            $case = new NotificationCase;
            $case->forceFill([
                'consultation_id' => $consultation->id(),
                'user_id' => $consultation->userId(),
                'previous_case_id' => $previous?->id,
                'case_number' => $caseNumber,
                'vin' => $mapped instanceof Vin ? $mapped->value() : null,
                'vin_key' => $mapped instanceof Vin ? $mapped->value() : null,
                'license_plate' => $criterion === CanonicalCriterion::PLATE ? $this->normalizer->normalize($consultation->valor()) : null,
                'make' => $this->normalizer->normalize($vehicle['marca']),
                'model' => $this->normalizer->normalize($vehicle['modelo']),
                'model_year' => is_numeric($vehicle['anio']) ? (int) $vehicle['anio'] : null,
                'status' => NotificationCaseStatus::PENDING,
                'notification_deadline_at' => $policy->deadline(CarbonImmutable::instance($consultation->createdAt())),
                'opened_at' => $now,
                'auto_close_at' => $policy->autoCloseAt($now),
                'lock_version' => 0,
                'creation_key' => 'consultation:'.$consultation->id(),
            ]);
            $case->save();

            $eventKey = 'case-created:'.$case->id;
            $this->audit->record($case->id, 'CASE_CREATED', $eventKey, $eventKey, $consultation->userId(), 'USER', null, 'PENDING', null, [
                'consultation_id' => $consultation->id(), 'case_number' => $caseNumber,
                'deadline_at' => $case->notification_deadline_at->format('Y-m-d H:i:s'),
            ]);
            $this->outbox->queue($case->id, $case->user_id, 'CASE_CREATED', 'PORTAL', $eventKey.':portal', [
                'case_number' => $caseNumber, 'deadline_at' => $case->notification_deadline_at->format('Y-m-d H:i:s'), 'status' => 'PENDING',
            ]);
            $this->consumeReservation($reservationId);

            return $case;
        }, 3);
    }

    private function findApplicableByVin(string $vin, CarbonImmutable $consultedAt): ?NotificationCase
    {
        $cases = NotificationCase::where('vin_key', $vin)->orderByDesc('opened_at')->orderByDesc('id')->lockForUpdate()->get();
        foreach ($cases as $case) {
            if ($case->status->isPending()) {
                return $case;
            }
            if ($case->status === NotificationCaseStatus::VALIDATED && $case->validated_at
                && $consultedAt->lessThanOrEqualTo(CarbonImmutable::instance($case->validated_at)->addDays($this->settings->reuseDays()))) {
                return $case;
            }
        }

        return null;
    }

    private function nextCaseNumber(int $year): string
    {
        DB::table('notification_case_sequences')->insertOrIgnore(['year' => $year, 'last_value' => 0, 'updated_at' => now()]);
        $row = DB::table('notification_case_sequences')->where('year', $year)->lockForUpdate()->first();
        $next = ((int) $row->last_value) + 1;
        DB::table('notification_case_sequences')->where('year', $year)->update(['last_value' => $next, 'updated_at' => now()]);

        return CaseNumber::fromSequence($year, $next)->value();
    }

    private function consumeReservation(int $reservationId): void
    {
        DB::table('notification_case_consultation_reservations')->where('id', $reservationId)
            ->whereNull('consumed_at')->whereNull('released_at')->update(['consumed_at' => now(), 'updated_at' => now()]);
    }
}
