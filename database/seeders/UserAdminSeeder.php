<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@vintrack.com.mx'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('Vintrack.2024!Admin'),
                'nombre' => 'Administrador',
                'telefono' => '',
                'rol' => 'admin',
                'activo' => true,
                'approved_at' => now(),
            ]
        );
    }
}
