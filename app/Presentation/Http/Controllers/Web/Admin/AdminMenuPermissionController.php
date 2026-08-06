<?php

namespace App\Presentation\Http\Controllers\Web\Admin;

use App\Infrastructure\Persistence\Models\AdminMenuPermission;
use App\Models\Role;
use App\Presentation\Support\RoleHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminMenuPermissionController
{
    public function index()
    {
        $permissions = AdminMenuPermission::with(['role', 'menuItem'])
            ->orderBy('id_rol')
            ->orderBy('display_order')
            ->get()
            ->groupBy(fn ($permission) => $permission->role?->nombre ?? 'sin_rol');

        $roles = Role::pluck('descripcion', 'nombre')->all();

        return view('admin.menu-permissions.index', compact('permissions', 'roles'));
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
                AdminMenuPermission::where('id', $id)->update([
                    'enabled' => ! empty($values['enabled']),
                    'display_order' => (int) $values['display_order'],
                ]);
            }
        });

        return redirect()->route('admin.menu-permissions.index')
            ->with('status', 'Permisos de menú actualizados correctamente.');
    }
}
