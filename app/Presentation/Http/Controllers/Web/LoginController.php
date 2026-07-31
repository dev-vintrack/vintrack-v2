<?php

namespace App\Presentation\Http\Controllers\Web;

use App\Application\Auth\Services\OtpService;
use App\Models\User;
use App\Presentation\Support\RoleHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class LoginController
{
    private const MAX_ATTEMPTS = 3;
    private const BLOCK_MINUTES = 10;

    public function __construct(private readonly OtpService $otpService)
    {
    }

    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $attemptsKey = 'login_attempts';
        $lastAttemptKey = 'login_last_attempt';

        $attempts = session($attemptsKey, 0);
        $lastAttempt = session($lastAttemptKey, 0);

        if ($attempts >= self::MAX_ATTEMPTS) {
            $remaining = self::BLOCK_MINUTES * 60 - (time() - $lastAttempt);

            if ($remaining > 0) {
                return redirect()->route('login')->withErrors([
                    'email' => 'Demasiados intentos. Intenta nuevamente en ' . ceil($remaining / 60) . ' minutos.',
                ])->withInput();
            }

            session([$attemptsKey => 0]);
        }

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            session([
                $attemptsKey => $attempts + 1,
                $lastAttemptKey => time(),
            ]);

            return redirect()->route('login')->withErrors([
                'email' => 'Las credenciales no coinciden con nuestros registros.',
            ])->withInput();
        }

        if (! $user->activo) {
            return redirect()->route('login')->withErrors([
                'email' => 'Tu cuenta está desactivada. Contacta a soporte.',
            ])->withInput();
        }

        if (RoleHelper::requiresApproval($user->rol) && $user->status !== 'active') {
            return redirect()->route('login')->withErrors([
                'email' => 'Tu solicitud aún está pendiente de aprobación.',
            ])->withInput();
        }

        $otp = $this->otpService->generateForSession($user->email, 'login');

        $sent = $this->otpService->send(
            $user->email,
            $otp,
            'Código de acceso VINTRACK',
            'Código de verificación'
        );

        if (! $sent) {
            return redirect()->route('login')->withErrors([
                'email' => 'No pudimos enviar el código. Verifica tu correo o intenta más tarde.',
            ])->withInput();
        }

        session()->put('login_user_id', $user->id);
        session()->put('login_remember', $request->boolean('remember', false));
        session([$attemptsKey => 0]);

        return redirect()->route('login.verify');
    }

    public function showVerifyForm(): View|RedirectResponse
    {
        if (! session('login_user_id') || ! session('login_otp')) {
            return redirect()->route('login');
        }

        return view('auth.login.verify');
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $userId = session('login_user_id');

        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);

        if (! $user) {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'otp' => 'required|digits:6',
        ]);

        if (! $this->otpService->validateSession($user->email, $data['otp'], 'login')) {
            return back()->withErrors(['otp' => 'El código es incorrecto o ha expirado.'])->withInput();
        }

        Auth::login($user, session('login_remember', false));
        $request->session()->regenerate();

        $this->otpService->clear(type: 'login');
        session()->forget(['login_user_id', 'login_remember']);

        return redirect()->intended(RoleHelper::homeRoute($user));
    }

    public function resendOtp(): RedirectResponse
    {
        $userId = session('login_user_id');

        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);

        if (! $user) {
            return redirect()->route('login');
        }

        $otp = $this->otpService->generateForSession($user->email, 'login');

        $sent = $this->otpService->send(
            $user->email,
            $otp,
            'Código de acceso VINTRACK',
            'Código de verificación'
        );

        if (! $sent) {
            return back()->withErrors([
                'otp' => 'No pudimos reenviar el código. Intenta más tarde.',
            ]);
        }

        return back()->with('status', 'Se ha enviado un nuevo código a tu correo.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
