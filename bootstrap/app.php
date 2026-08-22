<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'auth' => \App\Presentation\Http\Middleware\Authenticate::class,
            'role' => \App\Presentation\Http\Middleware\RequireRole::class,
            'active.customer' => \App\Presentation\Http\Middleware\RequireActiveCustomer::class,
            'customer.menu' => \App\Presentation\Http\Middleware\RequireCustomerMenuPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (HttpException $exception, Request $request) {
            if ($exception->getStatusCode() !== 419 || ! $exception->getPrevious() instanceof TokenMismatchException) {
                return null;
            }

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'code' => 'SESSION_EXPIRED',
                    'message' => 'Tu sesión expiró por inactividad. Por favor, ingresa nuevamente.',
                    'redirect' => route('login'),
                ], 419);
            }

            return redirect()->route('login')->with(
                'session_expired',
                'Tu sesión expiró por inactividad. Por favor, ingresa nuevamente.'
            );
        });
    })->create();
