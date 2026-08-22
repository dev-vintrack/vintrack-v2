<?php

namespace Tests\Unit\Infrastructure\External\Providers;

use App\Domain\Consultas\Entities\Consultation;
use App\Domain\Consultas\ValueObjects\ConsultationRequest;
use App\Domain\Consultas\ValueObjects\ProviderResultAssessment;
use App\Infrastructure\External\Providers\Placas\PlacasProviderAdapter;
use DateTimeImmutable;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PlacasProviderAssessmentTest extends TestCase
{
    #[DataProvider('realPgjPayloads')]
    public function test_real_pgj_object_and_list_payloads_persist_the_same_active_theft_projection(string $fixture): void
    {
        config()->set('providers.placas.token', 'test-token');
        $payload = file_get_contents(base_path("tests/Fixtures/ProviderResults/placas_service/$fixture"));
        $adapter = new PlacasProviderAdapter(new Client([
            'handler' => HandlerStack::create(new MockHandler([new Response(200, [], $payload)])),
        ]));

        $response = $adapter->consult(new ConsultationRequest(9, 'placas', '3VVVP65N4RM127029', 'niv', ['placas_service'], 'placas_service'));
        $consultation = Consultation::fromResponse(9, 4, 11, 'niv', '3VVVP65N4RM127029', ['placas_service'], 1, $response, new DateTimeImmutable);

        $this->assertTrue($response->success());
        $this->assertSame(1, $response->theftFlags()['pgj_robo']);
        $this->assertTrue($consultation->alertaRobo());
        $this->assertSame(ProviderResultAssessment::ACTIVE_QUALIFYING, $consultation->flagsJson()['_provider_result_assessment']['classification']);
    }

    public static function realPgjPayloads(): array
    {
        return [
            'consultation 64 PGJ object' => ['consultation-64-pgj-object.json'],
            'consultation 65 PGJ list' => ['consultation-65-pgj-list.json'],
        ];
    }
}
