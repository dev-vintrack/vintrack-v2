<?php

namespace Tests\Feature\Site;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_page_renders_default_message(): void
    {
        $this->get(route('site.sales'))
            ->assertOk()
            ->assertSee('Deseo compra un reporte completo por única ocasión de un vehículo Nacional/USA/Ambos')
            ->assertDontSee('con el VIN:');
    }

    public function test_sales_page_prefills_message_with_vin_from_query(): void
    {
        $vin = '4T1DAACK3SU551299';

        $this->get(route('site.sales', ['vin' => $vin]))
            ->assertOk()
            ->assertSee('Deseo compra un reporte completo por única ocasión de un vehículo Nacional/USA/Ambos con el VIN: ' . $vin);
    }

    public function test_sales_page_sanitizes_vin_query(): void
    {
        $this->get(route('site.sales', ['vin' => '4t1-daa-ck3 su55 1299']))
            ->assertOk()
            ->assertSee('con el VIN: 4T1DAACK3SU551299');
    }
}
