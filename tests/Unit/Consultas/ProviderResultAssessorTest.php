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

    private function fixturePath(string $serviceCode, string $fixture): string
    {
        return dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR.'ProviderResults'.DIRECTORY_SEPARATOR.$serviceCode.DIRECTORY_SEPARATOR.$fixture;
    }
}
