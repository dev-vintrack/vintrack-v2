<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('email', 'admin@vintrack.com.mx')->doesntExist()) {
            User::create([
                'name' => 'Administrador',
                'email' => 'admin@vintrack.com.mx',
                'password' => Hash::make('Vintrack.2024!Admin'),
                'nombre' => 'Administrador',
                'telefono' => null,
                'rol' => 'admin',
                'activo' => true,
                'approved_at' => now(),
            ]);
        }
    }
}
