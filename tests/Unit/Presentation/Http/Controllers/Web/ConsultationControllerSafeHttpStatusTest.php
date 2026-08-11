<?php

namespace Tests\Unit\Presentation\Http\Controllers\Web;

use App\Presentation\Http\Controllers\Web\ConsultationController;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Regresion: cuando un adaptador de proveedor (placas, vindata, etc.) reporta un
 * httpStatus crudo que Symfony/Laravel consideran "vacio" (204/304, via
 * Response::isEmpty()) o "invalido" (fuera de 100-599, via Response::isInvalid()),
 * usarlo directamente como status HTTP de la respuesta del endpoint /consult produce
 * un body JSON vacio o una excepcion, causando en el frontend:
 * "Error de red: Failed to execute 'json' on 'Response': Unexpected end of JSON input".
 *
 * ConsultationController::safeHttpStatus() debe normalizar esos casos a un status
 * valido y no vacio (502), preservando el status real en el campo "status" del body.
 */
class ConsultationControllerSafeHttpStatusTest extends TestCase
{
    #[DataProvider('unsafeStatusProvider')]
    public function test_unsafe_upstream_status_is_normalized(int $upstreamStatus, int $expected): void
    {
        $this->assertSame($expected, $this->callSafeHttpStatus($upstreamStatus));
    }

    public static function unsafeStatusProvider(): array
    {
        return [
            'connection failure (0)' => [0, 502],
            'informational (1xx)' => [102, 502],
            'no content (204)' => [204, 502],
            'not modified (304)' => [304, 502],
            'above valid range (600)' => [600, 502],
            'negative' => [-1, 502],
        ];
    }

    #[DataProvider('safeStatusProvider')]
    public function test_safe_upstream_status_is_preserved(int $upstreamStatus): void
    {
        $this->assertSame($upstreamStatus, $this->callSafeHttpStatus($upstreamStatus));
    }

    public static function safeStatusProvider(): array
    {
        return [
            'ok' => [200],
            'bad request' => [400],
            'forbidden' => [403],
            'not found' => [404],
            'payment required' => [402],
            'unprocessable entity' => [422],
            'internal server error' => [500],
            'bad gateway' => [502],
            'gateway timeout' => [504],
            'boundary 599' => [599],
        ];
    }

    private function callSafeHttpStatus(int $status): int
    {
        $controller = (new ReflectionMethod(ConsultationController::class, 'safeHttpStatus'))
            ->getDeclaringClass()
            ->newInstanceWithoutConstructor();

        $method = new ReflectionMethod(ConsultationController::class, 'safeHttpStatus');
        $method->setAccessible(true);

        return $method->invoke($controller, $status);
    }
}
