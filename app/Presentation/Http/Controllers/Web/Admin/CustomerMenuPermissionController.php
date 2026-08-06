<?php

namespace App\Presentation\Http\Controllers\Web\Admin;

use App\Infrastructure\Persistence\Models\CustomerMenuPermission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerMenuPermissionController
{
    public function index()
    {
        $permissions = CustomerMenuPermission::with(['role', 'menuItem'])
            ->whereHas('role', fn ($query) => $query->where('nombre', '!=', 'ocasional'))
            ->orderBy('id_rol')
            ->orderBy('display_order')
            ->get()
            ->groupBy(fn ($permission) => $permission->role?->nombre ?? 'sin_rol');

        $roles = Role::whereHas('roleType', fn ($query) => $query->where('is_customer', true))
            ->where('nombre', '!=', 'ocasional')
            ->pluck('descripcion', 'nombre')
            ->all();

        return view('admin.customer-menu-permissions.index', compact('permissions', 'roles'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'permissions' => 'required|array',
            'permissions.*.enabled' => 'nullable|boolean',
            'permissions.*.display_order' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['permissions'] as $id => $values) {
                CustomerMenuPermission::where('id', $id)->update([
                    'enabled' => ! empty($values['enabled']),
                    'display_order' => (int) $values['display_order'],
                ]);
            }
        });

        return redirect()->route('admin.customer-menu-permissions.index')
            ->with('status', 'Permisos de menú cliente actualizados correctamente.');
    }
}
