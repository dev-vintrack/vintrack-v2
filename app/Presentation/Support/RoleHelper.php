<?php

namespace App\Presentation\Support;

use App\Infrastructure\Persistence\Models\AdminMenuPermission;
use App\Models\User;

class RoleHelper
{
    public const ROLES = [
        'admin'              => 'Admin',
        'analista'           => 'Analista',
        'soporte'            => 'Soporte Técnico',
        'cliente_registrado' => 'Cliente Registrado',
        'perito'             => 'Cliente Perito',
        'oficial'            => 'Cliente con cargo Oficial',
        'ocasional'          => 'Cliente Ocasional',
    ];

    public const ADMIN_ROLES = ['admin', 'analista', 'soporte'];
    public const CUSTOMER_ROLES = ['cliente_registrado', 'perito', 'oficial', 'ocasional'];
    public const PENDING_APPROVAL_ROLES = ['perito', 'oficial'];

    public static function label(string $role): string
    {
        return self::ROLES[$role] ?? $role;
    }

    public static function isAdmin(User $user): bool
    {
        return in_array($user->rol, self::ADMIN_ROLES, true);
    }

    public static function isCustomer(User $user): bool
    {
        return in_array($user->rol, self::CUSTOMER_ROLES, true);
    }

    public static function canAccessAdmin(User $user): bool
    {
        return self::isAdmin($user);
    }

    public static function canManageUsers(User $user): bool
    {
        return in_array($user->rol, ['admin', 'soporte'], true);
    }

    /**
     * @return array<int, array{route_name: string, label: string, icon: string|null}>
     */
    public static function menuItemsFor(User $user): array
    {
        if (! self::isAdmin($user)) {
            return [];
        }

        return AdminMenuPermission::where('role', $user->rol)
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
        return in_array($role, self::PENDING_APPROVAL_ROLES, true);
    }

    public static function isApproved(User $user): bool
    {
        if (! self::requiresApproval($user->rol)) {
            return true;
        }

        return $user->status === 'active';
    }

    public static function homeRoute(User $user): string
    {
        return match ($user->rol) {
            'admin', 'analista', 'soporte' => route('home'),
            'cliente_registrado'           => route('home.cliente'),
            'perito'                       => route('home.perito'),
            'oficial'                      => route('home.oficial'),
            'ocasional'                    => route('home.ocasional'),
            default                        => route('home'),
        };
    }
}
