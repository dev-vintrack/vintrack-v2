<?php

namespace App\Presentation\Support;

use App\Infrastructure\Persistence\Models\AdminMenuPermission;
use App\Infrastructure\Persistence\Models\ProviderServiceRole;
use App\Models\Role;
use App\Models\User;

class RoleHelper
{
    /**
     * Fallback labels for legacy/seed contexts. Prefer the DB description.
     *
     * @var array<string, string>
     */
    public const ROLES = [
        'admin'              => 'Admin',
        'analista'           => 'Analista',
        'soporte'            => 'Soporte Técnico',
        'cliente_registrado' => 'Cliente Registrado',
        'perito'             => 'Cliente Perito',
        'oficial'            => 'Cliente con cargo Oficial',
        'ocasional'          => 'Cliente Ocasional',
    ];

    public static function label(string $role): string
    {
        return Role::where('nombre', $role)->value('descripcion') ?? self::ROLES[$role] ?? $role;
    }

    public static function isAdmin(User $user): bool
    {
        return $user->role?->roleType?->is_admin ?? false;
    }

    public static function isCustomer(User $user): bool
    {
        return $user->role?->roleType?->is_customer ?? false;
    }

    public static function canAccessAdmin(User $user): bool
    {
        return self::isAdmin($user);
    }

    public static function canManageUsers(User $user): bool
    {
        return self::isAdmin($user);
    }

    /**
     * @return array<int, array{route_name: string, label: string, icon: string|null}>
     */
    public static function menuItemsFor(User $user): array
    {
        if (! self::isAdmin($user)) {
            return [];
        }

        return AdminMenuPermission::where('id_rol', $user->id_rol)
            ->where('enabled', true)
            ->orderBy('display_order')
            ->get(['route_name', 'label', 'icon'])
            ->map(fn ($item) => [
                'route_name' => $item->route_name,
                'label' => $item->label,
                'icon' => $item->icon,
            ])
            ->all();
    }

    public static function requiresApproval(string $role): bool
    {
        return Role::where('nombre', $role)->value('requires_approval') ?? false;
    }

    public static function isApproved(User $user): bool
    {
        if (! self::requiresApproval($user->rol ?? '')) {
            return true;
        }

        return $user->status === 'active';
    }

    public static function homeRoute(User $user): string
    {
        if (self::isAdmin($user)) {
            return route('home');
        }

        $homeRoute = $user->role?->home_route;

        if ($homeRoute && \Illuminate\Support\Facades\Route::has($homeRoute)) {
            return route($homeRoute);
        }

        return route('home');
    }

    public static function allowedServiceIds(?int $idRol): array
    {
        if (! $idRol) {
            return [];
        }

        return ProviderServiceRole::where('id_rol', $idRol)
            ->where('status', true)
            ->pluck('provider_service_id')
            ->all();
    }

    public static function isServiceAllowed(?int $idRol, int $providerServiceId): bool
    {
        return in_array($providerServiceId, self::allowedServiceIds($idRol), true);
    }
}
