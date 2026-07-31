<?php

namespace App\Presentation\Http\Controllers\Web;

use App\Application\Auth\Services\OtpService;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ForgotPasswordController
{
    public function __construct(private readonly OtpService $otpService)
    {
    }

    public function showRequestForm(): View
    {
        return view('auth.forgot-password.email');
    }

    public function sendResetOtp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $data['email'])->first();

        if ($user) {
            $otp = $this->otpService->generateForSession($user->email, 'reset');

            $sent = $this->otpService->send(
                $user->email,
                $otp,
                'Recuperación de contraseña VINTRACK',
                'Código de recuperación'
            );

            if (! $sent) {
                return back()->withErrors([
                    'email' => 'No pudimos enviar el código. Verifica tu correo o intenta más tarde.',
                ])->withInput();
            }

            session()->put('reset_email', $user->email);
        }

        return redirect()->route('password.verify')
            ->with('status', 'Si el correo existe en nuestra base de datos, enviaremos un código.');
    }

    public function showVerifyForm(): View|RedirectResponse
    {
        if (! session('reset_email')) {
            return redirect()->route('password.request');
        }

        return view('auth.forgot-password.verify');
    }

    public function verifyResetOtp(Request $request): RedirectResponse
    {
        $email = session('reset_email');

        if (! $email) {
            return redirect()->route('password.request');
        }

        $data = $request->validate([
            'otp' => 'required|digits:6',
        ]);

        if (! $this->otpService->validateSession($email, $data['otp'], 'reset')) {
            return back()->withErrors(['otp' => 'El código es incorrecto o ha expirado.'])->withInput();
        }

        session()->put('reset_verified', true);

        return redirect()->route('password.reset');
    }

    public function resendResetOtp(): RedirectResponse
    {
        $email = session('reset_email');

        if (! $email) {
            return redirect()->route('password.request');
        }

        $otp = $this->otpService->generateForSession($email, 'reset');

        $sent = $this->otpService->send(
            $email,
            $otp,
            'Recuperación de contraseña VINTRACK',
            'Código de recuperación'
        );

        if (! $sent) {
            return back()->withErrors([
                'otp' => 'No pudimos reenviar el código. Intenta más tarde.',
            ]);
        }

        return back()->with('status', 'Se ha enviado un nuevo código a tu correo.');
    }
}
