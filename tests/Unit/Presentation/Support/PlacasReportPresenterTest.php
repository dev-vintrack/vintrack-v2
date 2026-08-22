<?php

namespace Tests\Unit\Presentation\Support;

use App\Presentation\Support\PlacasReportPresenter;
use Tests\TestCase;

class PlacasReportPresenterTest extends TestCase
{
    public function test_it_masks_a_recognized_historical_provider_implementation_failure(): void
    {
        $rows = PlacasReportPresenter::flatten([
            'data' => [
                'Message' => 'Cannot read properties of null (reading \'statusCode\')',
                'robo' => false,
            ],
        ]);

        $this->assertSame([['provider_status', 'Proveedor temporalmente no disponible.']], $rows);
        $this->assertSame('Estado del proveedor', PlacasReportPresenter::prettyKey('provider_status'));
        $this->assertFalse(PlacasReportPresenter::isAlertRow('CARFAX', 'Estado del proveedor', $rows[0][1]));
    }

    public function test_it_keeps_legitimate_provider_business_findings_visible(): void
    {
        $rows = PlacasReportPresenter::flatten([
            'data' => [
                'robo' => false,
                'Message' => 'El VIN no cuenta con reporte de robo.',
            ],
        ]);

        $this->assertContains(['data.robo', 'No'], $rows);
        $this->assertContains(['data.Message', 'El VIN no cuenta con reporte de robo.'], $rows);
    }
}
