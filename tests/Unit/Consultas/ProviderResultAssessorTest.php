<?php

namespace Tests\Unit\Consultas;

use App\Domain\Consultas\Services\ProviderResultAssessor;
use App\Domain\Consultas\ValueObjects\ProviderResultAssessment;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class ProviderResultAssessorTest extends TestCase
{
    #[DataProvider('contractFixtures')]
    public function test_it_assesses_versioned_provider_contract_fixtures(
        string $serviceCode,
        string $fixture,
        string $expectedClassification,
        bool $qualifies,
    ): void {
        $payload = json_decode((string) file_get_contents($this->fixturePath($serviceCode, $fixture)), true, 512, JSON_THROW_ON_ERROR);

        $assessment = app(ProviderResultAssessor::class)->assess($serviceCode, $payload);

        $this->assertSame($expectedClassification, $assessment->toArray()['classification']);
        $this->assertSame($qualifies, $assessment->qualifies());
        $this->assertSame($serviceCode, $assessment->toArray()['service_code']);
        $this->assertSame(ProviderResultAssessment::VERSION, $assessment->toArray()['version']);
    }

    public static function contractFixtures(): array
    {
        return [
            'placas current PGJ report' => ['placas_service', 'active-pgj.json', ProviderResultAssessment::ACTIVE_QUALIFYING, true],
            'consultation 64 real PGJ object remains active despite recovered OCRA' => ['placas_service', 'consultation-64-pgj-object.json', ProviderResultAssessment::ACTIVE_QUALIFYING, true],
            'consultation 65 real PGJ list remains active despite recovered OCRA' => ['placas_service', 'consultation-65-pgj-list.json', ProviderResultAssessment::ACTIVE_QUALIFYING, true],
            'placas recovered PGJ history' => ['placas_service', 'historical-recovered-pgj.json', ProviderResultAssessment::HISTORICAL_RECORD, false],
            'placas unmapped RAPI crime warning' => ['placas_service', 'warning-rapi.json', ProviderResultAssessment::NON_QUALIFYING_WARNING, false],
            'placas CARFAX false is clear' => ['placas_service', 'clear-carfax.json', ProviderResultAssessment::CLEAR, false],
            'placas unknown payload fails closed' => ['placas_service', 'unknown.json', ProviderResultAssessment::INDETERMINATE, false],
            'NMVTIS active theft' => ['nmvtis_plus', 'active-theft.json', ProviderResultAssessment::ACTIVE_QUALIFYING, true],
            'NMVTIS recovered theft history' => ['nmvtis_plus', 'historical-recovered-theft.json', ProviderResultAssessment::HISTORICAL_RECORD, false],
            'NMVTIS title brand warning' => ['nmvtis_plus', 'warning-title-brand.json', ProviderResultAssessment::NON_QUALIFYING_WARNING, false],
            'NMVTIS clear report' => ['nmvtis_plus', 'clear.json', ProviderResultAssessment::CLEAR, false],
            'NMVTIS unknown payload fails closed' => ['nmvtis_plus', 'unknown.json', ProviderResultAssessment::INDETERMINATE, false],
        ];
    }

    public function test_placas_preserves_active_and_historical_evidence_when_pgj_is_a_list(): void
    {
        $payload = json_decode((string) file_get_contents($this->fixturePath('placas_service', 'consultation-65-pgj-list.json')), true, 512, JSON_THROW_ON_ERROR);

        $assessment = app(ProviderResultAssessor::class)->assess('placas_service', $payload)->toArray();

        $this->assertSame(ProviderResultAssessment::ACTIVE_QUALIFYING, $assessment['classification']);
        $this->assertContains('pgj.0.ID_ESTATUS_VHI_ROBO=1', $assessment['predicates']);
        $this->assertContains('ocra.conReporteRoboRecuperacion=true+reporte.roboORecuperacion=2', $assessment['evidence_paths']);
    }

    public function test_placas_marks_provider_errors_indeterminate_without_an_active_predicate(): void
    {
        $assessment = app(ProviderResultAssessor::class)->assess('placas_service', [
            'pgj' => ['statusCode' => 401, 'message' => 'reCaptcha'],
            'ocra' => ['path' => '/ocra', 'status' => 502],
            'aviso' => ['error' => 'unavailable'],
        ])->toArray();

        $this->assertSame(ProviderResultAssessment::INDETERMINATE, $assessment['classification']);
        $this->assertSame(['source_unavailable.pgj', 'source_unavailable.ocra', 'source_unavailable.aviso'], $assessment['predicates']);
    }

    private function fixturePath(string $serviceCode, string $fixture): string
    {
        return dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR.'ProviderResults'.DIRECTORY_SEPARATOR.$serviceCode.DIRECTORY_SEPARATOR.$fixture;
    }
}
