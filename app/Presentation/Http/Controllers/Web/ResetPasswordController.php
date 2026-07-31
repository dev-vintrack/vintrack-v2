<?php

namespace App\Presentation\Http\Controllers\Web;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ResetPasswordController
{
    public function showResetForm(): View|RedirectResponse
    {
        if (! session('reset_verified') || ! session('reset_email')) {
            return redirect()->route('password.request');
        }

        return view('auth.forgot-password.reset');
    }

    public function reset(Request $request): RedirectResponse
    {
        if (! session('reset_verified') || ! session('reset_email')) {
            return redirect()->route('password.request');
        }

        $data = $request->validate([
            'password' => 'required|string|min:8|confirmed|regex:/^(?=.*[A-Z])(?=.*\d).+$/',
        ]);

        $user = User::where('email', session('reset_email'))->first();

        if (! $user) {
            return redirect()->route('password.request')->withErrors([
                'email' => 'No se encontró la cuenta.',
            ]);
        }

        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        session()->forget(['reset_email', 'reset_verified', 'reset_otp']);

        return redirect()->route('login')->with('status', 'Contraseña actualizada correctamente.');
    }
}
