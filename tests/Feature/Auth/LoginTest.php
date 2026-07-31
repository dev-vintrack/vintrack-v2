<?php

namespace Tests\Feature\Auth;

use App\Application\Auth\Services\OtpService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_accessible(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Iniciar sesión');
    }

    public function test_user_with_valid_credentials_is_redirected_to_otp(): void
    {
        Mail::fake();

        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'secret123',
            'nombre' => 'Administrador',
            'rol' => 'admin',
            'activo' => true,
            'approved_at' => now(),
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('login.verify'));
        $this->assertGuest();
        $this->assertNotNull(session('login_user_id'));
        $this->assertEquals($user->id, session('login_user_id'));
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $response = $this->post('/login', [
            'email' => 'wrong@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_user_can_verify_otp_and_authenticate(): void
    {
        Mail::fake();

        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'secret123',
            'nombre' => 'Administrador',
            'rol' => 'admin',
            'activo' => true,
            'approved_at' => now(),
        ]);

        $otp = app(OtpService::class)->generateForSession($user->email, 'login');

        $this->withSession([
            'login_user_id' => $user->id,
            'login_remember' => false,
        ]);

        $response = $this->post('/login/verificar', [
            'otp' => $otp,
        ]);

        $response->assertRedirect('/home');
        $this->assertAuthenticatedAs($user);
    }
}
