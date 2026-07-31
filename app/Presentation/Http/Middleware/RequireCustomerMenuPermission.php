<?php

namespace App\Presentation\Http\Middleware;

use App\Infrastructure\Persistence\Models\CustomerMenuPermission;
use App\Presentation\Support\RoleHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RequireCustomerMenuPermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('web')->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! RoleHelper::isCustomer($user)) {
            abort(403, 'No tienes permisos para acceder a esta sección.');
        }

        $routeName = $request->route()?->getName();

        if (! $routeName) {
            abort(403, 'No tienes permisos para acceder a esta sección.');
        }

        if (! $this->hasMenuPermission($user, $routeName)) {
            abort(403, 'No tienes permisos para acceder a esta sección.');
        }

        return $next($request);
    }

    private function hasMenuPermission($user, string $routeName): bool
    {
        return CustomerMenuPermission::where('id_rol', $user->id_rol)
            ->where('route_name', $routeName)
            ->where('enabled', true)
            ->exists();
    }
}
