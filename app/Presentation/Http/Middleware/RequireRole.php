<?php

namespace App\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RequireRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = Auth::guard('web')->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->activo) {
            abort(403, 'Tu cuenta está desactivada.');
        }

        $allowedRoles = array_merge($roles, ['admin']);
        if (! in_array($user->rol, $allowedRoles, true)) {
            abort(403, 'No tienes permisos para acceder a esta sección.');
        }

        return $next($request);
    }
}
