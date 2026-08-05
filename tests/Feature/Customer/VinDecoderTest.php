<?php

namespace Tests\Feature\Customer;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VinDecoderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CustomerMenuPermissionSeeder::class);
    }

    public function test_vin_decoder_page_renders_for_customer(): void
    {
        $customer = User::factory()->create([
            'rol' => 'cliente_registrado',
            'activo' => true,
            'status' => 'active',
            'approved_at' => now(),
        ]);

        $this->actingAs($customer)
            ->get(route('customer.vin-decoder'))
            ->assertOk()
            ->assertSee('Decodificador Vehicular')
            ->assertSee('vinDecoderForm')
            ->assertSee('VIN Propuesto', false)
            ->assertSee('calcTable', false)
            ->assertSee('btnShowCalc', false)
            ->assertSee('res-proposed-row', false)
            ->assertSee(route('site.decode-vin'), false);
    }

    public function test_unauthenticated_users_are_redirected_from_vin_decoder(): void
    {
        $this->get(route('customer.vin-decoder'))
            ->assertRedirect(route('login'));
    }
}
