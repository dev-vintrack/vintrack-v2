<?php

namespace App\Presentation\Http\Middleware;

use App\Presentation\Support\RoleHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RequireActiveCustomer
{
    private const ALLOWED_ROLES = ['cliente_registrado', 'perito', 'oficial'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('web')->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->activo) {
            abort(403, 'Tu cuenta está desactivada.');
        }

        if (! in_array($user->rol, self::ALLOWED_ROLES, true) || ! RoleHelper::isCustomer($user)) {
            abort(403, 'No tienes permisos para acceder a esta sección.');
        }

        if (RoleHelper::requiresApproval($user->rol) && $user->status !== 'active') {
            return redirect()->route('home.pending');
        }

        return $next($request);
    }
}
