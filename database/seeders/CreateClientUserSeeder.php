<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CreateClientUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = 'cliente.demo@vintrack.com.mx';

        if (User::where('email', $email)->exists()) {
            return;
        }

        User::create([
            'name' => 'Cliente Demo',
            'email' => $email,
            'password' => Hash::make('Cli2026Jul14?'),
            'nombre' => 'Cliente Demo',
            'telefono' => null,
            'rol' => 'cliente_registrado',
            'activo' => true,
            'status' => 'active',
            'approved_at' => now(),
        ]);
    }
}
