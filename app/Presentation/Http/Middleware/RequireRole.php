<?php

namespace App\Presentation\Http\Middleware;

use App\Infrastructure\Persistence\Models\AdminMenuPermission;
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
        if (in_array($user->rol, $allowedRoles, true)) {
            return $next($request);
        }

        // No permitimos que el fallback por menú supere una ruta exclusiva de admin.
        if ($roles === ['admin']) {
            abort(403, 'No tienes permisos para acceder a esta sección.');
        }

        $routeName = $request->route()?->getName();
        if ($routeName && $this->hasMenuPermission($user, $routeName)) {
            return $next($request);
        }

        abort(403, 'No tienes permisos para acceder a esta sección.');
    }

    private function hasMenuPermission($user, string $routeName): bool
    {
        return AdminMenuPermission::where('id_rol', $user->id_rol)
            ->where('route_name', $routeName)
            ->where('enabled', true)
            ->exists();
    }
}
