<?php

namespace App\Presentation\Http\Controllers\Web;

use App\Application\Auth\Services\OtpService;
use App\Models\Role;
use App\Models\User;
use App\Presentation\Support\RoleHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisterController
{
    public function __construct(private readonly OtpService $otpService)
    {
    }

    public function showEmailForm(): View
    {
        return view('auth.register.email');
    }

    public function sendEmailOtp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => 'required|email|max:255|unique:users',
        ]);

        $otp = $this->otpService->generateForSession($data['email'], 'register');

        $sent = $this->otpService->send(
            $data['email'],
            $otp,
            'Código de verificación VINTRACK',
            'Verificación de correo'
        );

        if (! $sent) {
            return back()->withErrors([
                'email' => 'No pudimos enviar el código. Verifica tu correo o intenta más tarde.',
            ])->withInput();
        }

        session()->put('register', [
            'email' => $data['email'],
            'verified' => false,
        ]);

        return redirect()->route('register.verify');
    }

    public function showVerifyForm(): View|RedirectResponse
    {
        if (! session('register')) {
            return redirect()->route('register');
        }

        return view('auth.register.verify');
    }

    public function verifyEmailOtp(Request $request): RedirectResponse
    {
        $register = session('register');

        if (! $register) {
            return redirect()->route('register');
        }

        $data = $request->validate([
            'otp' => 'required|digits:6',
        ]);

        if (! $this->otpService->validateSession($register['email'], $data['otp'], 'register')) {
            return back()->withErrors(['otp' => 'El código es incorrecto o ha expirado.'])->withInput();
        }

        session()->put('register.verified', true);

        return redirect()->route('register.data');
    }

    public function resendEmailOtp(): RedirectResponse
    {
        $register = session('register');

        if (! $register) {
            return redirect()->route('register');
        }

        $otp = $this->otpService->generateForSession($register['email'], 'register');

        $sent = $this->otpService->send(
            $register['email'],
            $otp,
            'Código de verificación VINTRACK',
            'Verificación de correo'
        );

        if (! $sent) {
            return back()->withErrors([
                'otp' => 'No pudimos reenviar el código. Intenta más tarde.',
            ]);
        }

        return back()->with('status', 'Se ha enviado un nuevo código a tu correo.');
    }

    public function showDataForm(): View|RedirectResponse
    {
        $register = session('register');

        if (! $register || ! ($register['verified'] ?? false)) {
            return redirect()->route('register');
        }

        return view('auth.register.data');
    }

    public function store(Request $request): RedirectResponse
    {
        $register = session('register');

        if (! $register || ! ($register['verified'] ?? false)) {
            return redirect()->route('register');
        }

        $data = $request->validate([
            'nombre'          => 'required|string|max:128',
            'telefono'        => 'required|string|regex:/^[0-9]{10}$/',
            'password'        => 'required|string|min:8|confirmed|regex:/^(?=.*[A-Z])(?=.*\d).+$/',
            'es_oficial'      => 'nullable|boolean',
            'entidad'         => 'nullable|required_if:es_oficial,1|string|max:128',
        ]);

        $esOficial = ! empty($data['es_oficial']);
        $roleName  = $esOficial ? 'oficial' : 'cliente_registrado';
        $role      = Role::firstOrCreateByName($roleName);
        $status    = RoleHelper::requiresApproval($roleName) ? 'pending' : 'active';

        $user = User::create([
            'name'      => $data['nombre'],
            'email'     => $register['email'],
            'telefono'  => $data['telefono'],
            'password'  => Hash::make($data['password']),
            'nombre'    => $data['nombre'],
            'id_rol'    => $role?->id_rol,
            'rol'       => $roleName,
            'status'    => $status,
            'activo'             => $status === 'active',
            'es_oficial'         => $esOficial,
            'entidad'            => $esOficial ? ($data['entidad'] ?? null) : null,
            'email_verified_at'  => now(),
        ]);

        session()->forget('register');

        session()->put('register_success', [
            'nombre' => $user->nombre,
            'email'  => $user->email,
        ]);

        return redirect()->route('register.whatsapp');
    }

    public function showWhatsApp(): View|RedirectResponse
    {
        $success = session('register_success');

        if (! $success) {
            return redirect()->route('register');
        }

        $adminPhone = config('vintrack.admin_whatsapp', '525638040952');
        $mensaje = urlencode("Hola, solicito completar mi registro en VINTrack.com.mx. Mi nombre es {$success['nombre']} y confirmo que el correo {$success['email']} me pertenece para continuar con la activación segura de mi cuenta.");
        $whatsappUrl = "https://wa.me/{$adminPhone}?text={$mensaje}";

        return view('auth.register.whatsapp', compact('whatsappUrl'));
    }
}
