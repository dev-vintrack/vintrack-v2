<?php

namespace Tests\Feature\Auth;

use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SessionExpirationHandlingTest extends TestCase
{
    public function test_expired_html_form_redirects_to_login_with_a_clear_message(): void
    {
        $this->registerTokenMismatchRoute();

        $response = $this->post('/_test/token-mismatch');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas(
            'session_expired',
            'Tu sesión expiró por inactividad. Por favor, ingresa nuevamente.'
        );
    }

    public function test_expired_async_request_returns_a_safe_json_contract(): void
    {
        $this->registerTokenMismatchRoute();

        $response = $this->postJson('/_test/token-mismatch');

        $response
            ->assertStatus(419)
            ->assertJson([
                'code' => 'SESSION_EXPIRED',
                'message' => 'Tu sesión expiró por inactividad. Por favor, ingresa nuevamente.',
                'redirect' => route('login'),
            ]);
    }

    public function test_login_shows_the_session_expiration_message(): void
    {
        $this->withSession([
            'session_expired' => 'Tu sesión expiró por inactividad. Por favor, ingresa nuevamente.',
        ])->get('/login')
            ->assertOk()
            ->assertSee('Tu sesión expiró por inactividad. Por favor, ingresa nuevamente.')
            ->assertSee('js/session-expiry-handler.js');
    }

    private function registerTokenMismatchRoute(): void
    {
        Route::middleware('web')->post('/_test/token-mismatch', static function (): void {
            throw new TokenMismatchException;
        });
    }
}
