<?php

namespace Tests\Unit\NotificationCases;

use App\Application\NotificationCases\Services\ConsultationVinMapper;
use App\Domain\NotificationCases\Enums\CanonicalCriterion;
use App\Domain\NotificationCases\Enums\NotificationCaseStatus;
use App\Domain\NotificationCases\Services\NotificationCaseStateMachine;
use App\Domain\NotificationCases\Services\NotificationCaseTemporalPolicy;
use App\Domain\NotificationCases\Services\TextNormalizer;
use App\Domain\NotificationCases\ValueObjects\CaseNumber;
use App\Domain\NotificationCases\ValueObjects\Vin;
use Carbon\CarbonImmutable;
use DomainException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class NotificationCaseDomainTest extends TestCase
{
    public function test_exact_status_catalog_and_pending_projection(): void
    {
        $this->assertSame(['PENDING', 'SUBMITTED', 'UNDER_REVIEW', 'REJECTED', 'VALIDATED', 'CLOSED_NO_FOLLOW_UP'], array_column(NotificationCaseStatus::cases(), 'value'));
        foreach (NotificationCaseStatus::cases() as $status) {
            $this->assertSame(! in_array($status, [NotificationCaseStatus::VALIDATED, NotificationCaseStatus::CLOSED_NO_FOLLOW_UP], true), $status->isPending());
        }
    }

    public function test_state_machine_accepts_only_contractual_transitions(): void
    {
        $machine = new NotificationCaseStateMachine;
        $this->assertTrue($machine->canTransition(NotificationCaseStatus::PENDING, NotificationCaseStatus::SUBMITTED));
        $this->assertTrue($machine->canTransition(NotificationCaseStatus::UNDER_REVIEW, NotificationCaseStatus::VALIDATED));
        $this->assertFalse($machine->canTransition(NotificationCaseStatus::SUBMITTED, NotificationCaseStatus::VALIDATED));
        $this->assertFalse($machine->canTransition(NotificationCaseStatus::VALIDATED, NotificationCaseStatus::PENDING));
        $this->expectException(DomainException::class);
        $machine->assertTransition(NotificationCaseStatus::SUBMITTED, NotificationCaseStatus::VALIDATED);
    }

    public function test_normalizer_uppercases_removes_diacritics_and_collapses_spaces(): void
    {
        $normalizer = new TextNormalizer;
        $this->assertSame('ANGEL NUNEZ / RECUPERACION MEXICO', $normalizer->normalize('  Ángel   Núñez / recuperación México '));
        $this->assertSame('PINGUINO', $normalizer->normalize('pingüino'));
        $this->assertNull($normalizer->normalize('   '));
    }

    public function test_vin_criterion_and_folio_rules(): void
    {
        $this->assertSame(CanonicalCriterion::VIN, CanonicalCriterion::fromConsultation('niv'));
        $this->assertSame(CanonicalCriterion::VIN, CanonicalCriterion::fromConsultation('VIN'));
        $this->assertSame(CanonicalCriterion::PLATE, CanonicalCriterion::fromConsultation('placa'));
        $this->assertSame('1HGCM82633A123456', Vin::fromString('1hgcm82633a123456')->value());
        $this->assertSame('NT-2026-000042', CaseNumber::fromSequence(2026, 42)->value());
    }

    public function test_invalid_vin_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Vin::fromString('1HGCM82633A12345I');
    }

    public function test_plate_mapper_returns_explicit_absence_without_guessing_payload_path(): void
    {
        $mapped = (new ConsultationVinMapper)->map('placa', 'ABC1234', 'placas', ['nested' => ['vin' => '1HGCM82633A123456']]);
        $this->assertSame(ConsultationVinMapper::VIN_NOT_AVAILABLE, $mapped);
    }

    public function test_temporal_boundaries_are_exact_in_business_timezone(): void
    {
        $policy = new NotificationCaseTemporalPolicy('America/Mexico_City', 3, 30, 90);
        $consulted = CarbonImmutable::parse('2026-08-11 14:00:00', 'America/Mexico_City');
        $deadline = $policy->deadline($consulted);
        $this->assertSame('2026-08-14 23:59:59', $deadline->format('Y-m-d H:i:s'));
        $this->assertTrue($policy->deadlineIsCurrent($deadline, CarbonImmutable::parse('2026-08-14 23:59:59.999999', 'America/Mexico_City')));
        $this->assertFalse($policy->deadlineIsCurrent($deadline, CarbonImmutable::parse('2026-08-15 00:00:00', 'America/Mexico_City')));
        $this->assertSame('2026-09-10 14:00:00', $policy->autoCloseAt($consulted)->format('Y-m-d H:i:s'));
        $validated = CarbonImmutable::parse('2026-01-01 10:00:00', 'America/Mexico_City');
        $this->assertTrue($policy->validationIsReusable($validated, $validated->addDays(90)));
        $this->assertFalse($policy->validationIsReusable($validated, $validated->addDays(90)->addSecond()));
    }
}
