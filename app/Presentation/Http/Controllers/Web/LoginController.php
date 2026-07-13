<?php

namespace App\Presentation\Http\Controllers\Web;

use App\Presentation\Support\RoleHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class LoginController
{
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (Auth::guard('web')->attempt($credentials, $remember)) {
            $request->session()->regenerate();

            $user = Auth::guard('web')->user();

            if (! $user->activo) {
                Auth::guard('web')->logout();
                return redirect()->route('login')->withErrors([
                    'email' => 'Tu cuenta está desactivada. Contacta a soporte.',
                ]);
            }

            if (RoleHelper::requiresApproval($user->rol) && $user->status !== 'active') {
                Auth::guard('web')->logout();
                return redirect()->route('login')->withErrors([
                    'email' => 'Tu solicitud aún está pendiente de aprobación.',
                ]);
            }

            return redirect()->intended(RoleHelper::homeRoute($user));
        }

        return redirect()->route('login')->withErrors([
            'email' => 'Las credenciales no coinciden con nuestros registros.',
        ])->withInput();
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
