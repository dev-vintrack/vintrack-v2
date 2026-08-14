<?php

namespace App\Application\NotificationCases\Services;

use App\Domain\NotificationCases\Enums\CanonicalCriterion;
use App\Domain\NotificationCases\Enums\NotificationCaseStatus;
use App\Domain\NotificationCases\Services\NotificationCaseStateMachine;
use App\Domain\NotificationCases\Services\TextNormalizer;
use App\Domain\NotificationCases\ValueObjects\Vin;
use App\Infrastructure\Persistence\Models\NotificationCase;
use App\Models\User;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;

final class NotificationCaseLifecycleService
{
    private const TEXT_FIELDS = [
        'recovery_place', 'country', 'state', 'municipality', 'neighborhood', 'postal_code', 'street', 'street_number',
        'license_plate', 'make', 'model', 'engine_number', 'color', 'origin', 'authority', 'iph', 'nuc', 'investigation_file', 'safekeeping', 'inventory', 'notes',
    ];

    private const DRAFT_FIELDS = [...self::TEXT_FIELDS, 'recovered_at', 'model_year'];

    private const REJECTION_REASON_MAX_LENGTH = 2000;

    private const REQUIRED = ['vin', 'recovery_place', 'country', 'state', 'municipality', 'recovered_at', 'license_plate', 'make', 'model_year', 'origin', 'authority', 'investigation_file', 'safekeeping'];

    public function __construct(
        private readonly NotificationCaseAuthorizationService $authorization,
        private readonly NotificationCaseStateMachine $stateMachine,
        private readonly TextNormalizer $normalizer,
        private readonly NotificationCaseAuditService $audit,
        private readonly NotificationCaseOutboxService $outbox,
        private readonly NotificationCaseSettings $settings,
    ) {}

    /** @param array<string, mixed> $fields */
    public function saveDraft(User $actor, int $caseId, array $fields, int $expectedVersion, string $requestKey): NotificationCase
    {
        return DB::transaction(function () use ($actor, $caseId, $fields, $expectedVersion, $requestKey) {
            $eventKey = 'case-draft:'.hash('sha256', $requestKey);
            if ($prior = DB::table('notification_case_events')->where('event_key', $eventKey)->first()) {
                return NotificationCase::findOrFail($prior->notification_case_id);
            }
            $case = NotificationCase::lockForUpdate()->findOrFail($caseId);
            if (! $this->authorization->canEditOwn($actor, $case)) {
                throw new DomainException('No autorizado para editar este expediente.');
            }
            $this->assertVersion($case, $expectedVersion);
            $allowed = array_intersect_key($fields, array_flip(self::DRAFT_FIELDS));
            foreach (self::TEXT_FIELDS as $field) {
                if (array_key_exists($field, $allowed)) {
                    $allowed[$field] = $this->normalizer->normalize($allowed[$field]);
                }
            }
            if (array_key_exists('recovered_at', $allowed) && $allowed['recovered_at'] !== null && $allowed['recovered_at'] !== '') {
                $recoveredAt = CarbonImmutable::parse((string) $allowed['recovered_at'], $this->settings->timezone());
                if ($recoveredAt->greaterThan(CarbonImmutable::now($this->settings->timezone()))) {
                    throw new DomainException('La fecha y hora de recuperación no puede estar en el futuro.');
                }
                $allowed['recovered_at'] = $recoveredAt;
            }
            $case->forceFill($allowed);
            $case->lock_version++;
            $case->save();
            $this->audit->record($case->id, 'CASE_DRAFT_UPDATED', $eventKey, $requestKey, $actor->id, 'USER', $case->status->value, $case->status->value, null, [
                'fields' => array_keys($allowed), 'lock_version' => $case->lock_version,
            ], $requestKey);

            return $case;
        }, 3);
    }

    /** @param array<string, mixed> $fields */
    public function saveAdministrativeCorrections(User $actor, int $caseId, array $fields, int $expectedVersion, string $requestKey): NotificationCase
    {
        return DB::transaction(function () use ($actor, $caseId, $fields, $expectedVersion, $requestKey) {
            $case = NotificationCase::lockForUpdate()->findOrFail($caseId);
            if (! $this->authorization->canEditAsAdministrator($actor, $case)) {
                throw new DomainException('No autorizado para corregir este expediente en su estado actual.');
            }
            $this->assertVersion($case, $expectedVersion);
            $allowed = array_intersect_key($fields, array_flip(self::DRAFT_FIELDS));
            foreach (self::TEXT_FIELDS as $field) {
                if (array_key_exists($field, $allowed)) {
                    $allowed[$field] = $this->normalizer->normalize($allowed[$field]);
                }
            }
            if (array_key_exists('recovered_at', $allowed) && $allowed['recovered_at'] !== null && $allowed['recovered_at'] !== '') {
                $recoveredAt = CarbonImmutable::parse((string) $allowed['recovered_at'], $this->settings->timezone());
                if ($recoveredAt->greaterThan(CarbonImmutable::now($this->settings->timezone()))) {
                    throw new DomainException('La fecha y hora de recuperaciÃ³n no puede estar en el futuro.');
                }
                $allowed['recovered_at'] = $recoveredAt;
            }

            $changes = [];
            foreach ($allowed as $field => $value) {
                $old = $case->{$field};
                $oldComparable = $old instanceof \DateTimeInterface ? $old->format('Y-m-d H:i:s') : $old;
                $newComparable = $value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i:s') : $value;
                if ((string) ($oldComparable ?? '') !== (string) ($newComparable ?? '')) {
                    $changes[$field] = [$oldComparable, $value, $newComparable];
                }
            }
            if ($changes === []) {
                return $case;
            }

            $case->forceFill(array_map(fn (array $change) => $change[1], $changes));
            $case->lock_version++;
            $case->save();
            foreach ($changes as $field => [$old, , $new]) {
                $eventKey = 'admin-field-corrected:'.hash('sha256', $requestKey.':'.$field);
                $this->audit->record($case->id, 'ADMIN_FIELD_CORRECTED', $eventKey, $requestKey, $actor->id, 'ANALYST', $case->status->value, $case->status->value, null, ['fields' => [$field], 'lock_version' => $case->lock_version], $requestKey, null, null, $field, $this->auditValue($old), $this->auditValue($new));
            }

            return $case;
        }, 3);
    }

    public function assignVinOnce(User $actor, int $caseId, string $vinValue, string $requestKey, ?string $ip = null, ?string $userAgent = null): NotificationCase
    {
        $result = DB::transaction(function () use ($actor, $caseId, $vinValue, $requestKey, $ip, $userAgent) {
            $eventKey = 'case-vin-assigned:'.hash('sha256', $requestKey);
            $prior = DB::table('notification_case_events')->where('event_key', $eventKey)->first();
            if ($prior) {
                return NotificationCase::findOrFail($prior->notification_case_id);
            }
            $case = NotificationCase::with('consultation')->lockForUpdate()->findOrFail($caseId);
            if (! $this->authorization->canAssignVinOnce($actor, $case)) {
                throw new DomainException('La asignación única de VIN no está autorizada.');
            }
            if (CanonicalCriterion::fromConsultation($case->consultation->criterio) !== CanonicalCriterion::PLATE) {
                throw new DomainException('Solo un borrador originado por placa admite asignación de VIN.');
            }
            $vin = Vin::fromString($vinValue)->value();
            DB::table('notification_case_vin_guards')->insertOrIgnore(['vin_key' => $vin, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('notification_case_vin_guards')->where('vin_key', $vin)->lockForUpdate()->first();
            $conflict = NotificationCase::where('vin_key', $vin)->where('id', '<>', $case->id)
                ->where(function ($query) {
                    $query->whereIn('status', ['PENDING', 'SUBMITTED', 'UNDER_REVIEW', 'REJECTED'])
                        ->orWhere(function ($q) {
                            $q->where('status', 'VALIDATED')->where('validated_at', '>=', now()->subDays($this->settings->reuseDays()));
                        });
                })->orderByDesc('opened_at')->lockForUpdate()->first();
            if ($conflict) {
                $incidentKey = 'vin-conflict:'.min($case->id, $conflict->id).':'.max($case->id, $conflict->id).':'.$vin;
                DB::table('notification_case_vin_reconciliations')->insertOrIgnore([
                    'notification_case_id' => $case->id,
                    'conflicting_case_id' => $conflict->id,
                    'incident_key' => $incidentKey,
                    'status' => 'OPEN',
                    'detected_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->audit->record($case->id, 'CASE_VIN_RECONCILIATION_DETECTED', 'event:'.$incidentKey, $requestKey, $actor->id, 'USER', $case->status->value, $case->status->value, 'VIN_CONFLICT', [
                    'conflicting_case_id' => $conflict->id,
                ], $requestKey, $ip, $userAgent);

                return ['conflict' => true];
            }
            $case->forceFill(['vin' => $vin, 'vin_key' => $vin, 'lock_version' => $case->lock_version + 1]);
            $case->save();
            $this->audit->record($case->id, 'CASE_VIN_ASSIGNED', $eventKey, $requestKey, $actor->id, 'USER', $case->status->value, $case->status->value, null, [
                'source' => 'OWNER_PLATE_DRAFT', 'lock_version' => $case->lock_version,
            ], $requestKey, $ip, $userAgent);

            return $case;
        }, 3);

        if (is_array($result)) {
            throw new DomainException('El VIN coincide con un expediente aplicable y requiere conciliación.');
        }

        return $result;
    }

    public function submit(User $actor, int $caseId, int $expectedVersion, string $requestKey): NotificationCase
    {
        return DB::transaction(function () use ($actor, $caseId, $expectedVersion, $requestKey) {
            $candidateKeys = ['case_submitted:'.hash('sha256', $requestKey), 'case_resubmitted:'.hash('sha256', $requestKey)];
            if ($prior = DB::table('notification_case_events')->whereIn('event_key', $candidateKeys)->first()) {
                return NotificationCase::findOrFail($prior->notification_case_id);
            }
            $case = NotificationCase::lockForUpdate()->findOrFail($caseId);
            if (! $this->authorization->canSubmit($actor, $case)) {
                throw new DomainException('No autorizado para enviar este expediente.');
            }
            $this->assertVersion($case, $expectedVersion);
            if (DB::table('notification_case_vin_reconciliations')->where('notification_case_id', $case->id)->where('status', 'OPEN')->exists()) {
                throw new DomainException('El expediente tiene una conciliación VIN pendiente.');
            }
            foreach (self::REQUIRED as $field) {
                if ($case->{$field} === null || $case->{$field} === '') {
                    throw new DomainException("Campo obligatorio faltante: {$field}.");
                }
            }
            if (! $case->iph && ! $case->nuc) {
                throw new DomainException('Debe informarse IPH o NUC.');
            }
            if ($case->recovered_at->greaterThan(CarbonImmutable::now($this->settings->timezone()))) {
                throw new DomainException('La fecha y hora de recuperación no puede estar en el futuro.');
            }
            $from = $case->status;
            $this->stateMachine->assertTransition($from, NotificationCaseStatus::SUBMITTED);
            $now = now($this->settings->timezone());
            $case->forceFill([
                'status' => NotificationCaseStatus::SUBMITTED,
                'submitted_at' => $case->submitted_at ?: $now,
                'last_submitted_at' => $now,
                'lock_version' => $case->lock_version + 1,
            ]);
            $case->save();
            $eventType = $from === NotificationCaseStatus::REJECTED ? 'CASE_RESUBMITTED' : 'CASE_SUBMITTED';
            $eventKey = strtolower($eventType).':'.hash('sha256', $requestKey);
            $this->audit->record($case->id, $eventType, $eventKey, $requestKey, $actor->id, 'USER', $from->value, 'SUBMITTED', null, ['lock_version' => $case->lock_version], $requestKey);
            $this->outbox->queueChannels($case->id, $case->user_id, $eventType, $eventKey, ['case_number' => $case->case_number, 'status' => 'SUBMITTED']);

            return $case;
        }, 3);
    }

    public function transition(User $actor, int $caseId, NotificationCaseStatus $to, int $expectedVersion, string $requestKey, ?string $reason = null): NotificationCase
    {
        return DB::transaction(function () use ($actor, $caseId, $to, $expectedVersion, $requestKey, $reason) {
            $eventType = match ($to) {
                NotificationCaseStatus::UNDER_REVIEW => 'CASE_REVIEW_STARTED',
                NotificationCaseStatus::REJECTED => 'CASE_REJECTED',
                NotificationCaseStatus::VALIDATED => 'CASE_VALIDATED',
                default => throw new DomainException('Transición administrativa no soportada.'),
            };
            $eventKey = strtolower($eventType).':'.hash('sha256', $requestKey);
            if ($prior = DB::table('notification_case_events')->where('event_key', $eventKey)->first()) {
                return NotificationCase::findOrFail($prior->notification_case_id);
            }
            $case = NotificationCase::lockForUpdate()->findOrFail($caseId);
            if (! $this->authorization->canReview($actor)) {
                throw new DomainException('No autorizado para transiciones analíticas.');
            }
            if ($to === NotificationCaseStatus::CLOSED_NO_FOLLOW_UP) {
                throw new DomainException('El cierre por falta de seguimiento no es seleccionable manualmente.');
            }
            if ($to === NotificationCaseStatus::VALIDATED && ! $this->authorization->canValidate($actor)) {
                throw new DomainException('No autorizado para validar.');
            }
            if ($to === NotificationCaseStatus::REJECTED) {
                $reason = $this->normalizer->normalize($reason);
                if ($reason === null || $reason === '') {
                    throw new DomainException('El rechazo requiere motivo.');
                }
                if (mb_strlen($reason) > self::REJECTION_REASON_MAX_LENGTH) {
                    throw new DomainException('El motivo de rechazo excede la longitud permitida.');
                }
            }
            $this->assertVersion($case, $expectedVersion);
            $from = $case->status;
            $this->stateMachine->assertTransition($from, $to);
            if ($to === NotificationCaseStatus::VALIDATED) {
                $this->assertComplete($case);
            }
            $timeField = match ($to) {
                NotificationCaseStatus::UNDER_REVIEW => 'review_started_at',
                NotificationCaseStatus::REJECTED => 'rejected_at',
                NotificationCaseStatus::VALIDATED => 'validated_at',
                default => null,
            };
            $values = ['status' => $to, 'lock_version' => $case->lock_version + 1];
            if ($timeField) {
                $values[$timeField] = now($this->settings->timezone());
            }
            $case->forceFill($values)->save();
            $this->audit->record($case->id, $eventType, $eventKey, $requestKey, $actor->id, 'ANALYST', $from->value, $to->value, $reason, ['lock_version' => $case->lock_version], $requestKey);
            $this->outbox->queueChannels($case->id, $case->user_id, $eventType, $eventKey, [
                'case_number' => $case->case_number,
                'status' => $to->value,
                'message' => $to === NotificationCaseStatus::REJECTED ? $reason : null,
            ], in_array($eventType, ['CASE_REJECTED', 'CASE_VALIDATED'], true));

            return $case;
        }, 3);
    }

    public function autoCloseDue(int $limit = 100, ?int $caseId = null, ?int $expectedVersion = null): int
    {
        $ids = NotificationCase::whereIn('status', ['PENDING', 'SUBMITTED', 'UNDER_REVIEW', 'REJECTED'])
            ->where('auto_close_at', '<=', now($this->settings->timezone()))
            ->when($caseId !== null, fn ($query) => $query->whereKey($caseId))
            ->when($expectedVersion !== null, fn ($query) => $query->where('lock_version', $expectedVersion))
            ->orderBy('auto_close_at')->orderBy('id')->limit($limit)->pluck('id');
        $closed = 0;
        foreach ($ids as $id) {
            $didClose = DB::transaction(function () use ($id, $expectedVersion) {
                $case = NotificationCase::lockForUpdate()->find($id);
                if (! $case || ($expectedVersion !== null && $case->lock_version !== $expectedVersion) || ! $case->status->isPending() || $case->auto_close_at->isFuture()) {
                    return false;
                }
                $from = $case->status;
                $this->stateMachine->assertTransition($from, NotificationCaseStatus::CLOSED_NO_FOLLOW_UP);
                $case->forceFill(['status' => NotificationCaseStatus::CLOSED_NO_FOLLOW_UP, 'closed_at' => now($this->settings->timezone()), 'lock_version' => $case->lock_version + 1])->save();
                $eventKey = 'case-auto-closed:'.$case->id.':'.$case->auto_close_at->format('YmdHis');
                $this->audit->record($case->id, 'CASE_AUTO_CLOSED', $eventKey, $eventKey, null, 'SYSTEM', $from->value, 'CLOSED_NO_FOLLOW_UP', null, ['lock_version' => $case->lock_version]);
                $this->outbox->queueChannels($case->id, $case->user_id, 'CASE_AUTO_CLOSED', $eventKey, ['case_number' => $case->case_number, 'status' => 'CLOSED_NO_FOLLOW_UP']);

                return true;
            }, 3);
            $closed += $didClose ? 1 : 0;
        }

        return $closed;
    }

    private function assertVersion(NotificationCase $case, int $expected): void
    {
        if ($case->lock_version !== $expected) {
            throw new DomainException('Conflicto de versión del expediente.');
        }
    }

    private function assertComplete(NotificationCase $case): void
    {
        foreach (self::REQUIRED as $field) {
            if ($case->{$field} === null || $case->{$field} === '') {
                throw new DomainException("Campo obligatorio faltante: {$field}.");
            }
        }
        if (! $case->iph && ! $case->nuc) {
            throw new DomainException('Debe informarse IPH o NUC.');
        }
        if ($case->recovered_at->greaterThan(CarbonImmutable::now($this->settings->timezone()))) {
            throw new DomainException('La fecha y hora de recuperaciÃ³n no puede estar en el futuro.');
        }
    }

    private function auditValue(mixed $value): ?string
    {
        return $value === null ? null : mb_substr((string) $value, 0, 65535);
    }
}
