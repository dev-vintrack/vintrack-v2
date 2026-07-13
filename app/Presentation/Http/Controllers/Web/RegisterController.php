<?php

namespace App\Presentation\Http\Controllers\Web;

use App\Models\User;
use App\Presentation\Support\RoleHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterController
{
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:128',
            'email'     => 'required|string|email|max:255|unique:users',
            'telefono'  => 'required|string|max:32',
            'password'  => 'required|string|min:8|confirmed',
            'role_type' => 'required|in:cliente_registrado,perito,oficial',
        ]);

        $role = $data['role_type'];
        $status = RoleHelper::requiresApproval($role) ? 'pending' : 'active';

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'telefono'  => $data['telefono'],
            'password'  => Hash::make($data['password']),
            'rol'       => $role,
            'status'    => $status,
            'activo'    => true,
        ]);

        Auth::login($user);

        if ($status === 'pending') {
            return redirect()->route('home.pending')
                ->with('status', 'Tu solicitud ha sido enviada. Un administrador la revisará y activará tu cuenta.');
        }

        return redirect()->intended(RoleHelper::homeRoute($user));
    }
}
