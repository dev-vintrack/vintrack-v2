<?php

namespace App\Presentation\Http\Controllers\Web\Admin;

use App\Application\Roles\Services\RoleBootstrapService;
use App\Models\Role;
use App\Models\RoleType;
use Illuminate\Http\Request;

class AdminRoleController
{
    public function __construct(private RoleBootstrapService $bootstrapService)
    {
    }

    public function index()
    {
        $roles = Role::with('roleType')
            ->orderBy('display_order')
            ->orderBy('nombre')
            ->get();

        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        $roleTypes = $this->roleTypeOptions();

        return view('admin.roles.create', compact('roleTypes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:32|unique:roles,nombre',
            'descripcion' => 'nullable|string|max:255',
            'role_type_id' => 'required|exists:role_types,id',
            'home_route' => 'nullable|string|max:64',
            'requires_approval' => 'required|boolean',
            'status' => 'required|boolean',
            'display_order' => 'required|integer|min:0',
        ]);

        $role = Role::create([
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'],
            'role_type_id' => $data['role_type_id'],
            'home_route' => $data['home_route'],
            'requires_approval' => $data['requires_approval'],
            'status' => $data['status'],
            'display_order' => $data['display_order'],
        ]);

        $this->bootstrapService->bootstrap($role);

        return redirect()->route('admin.roles.index')
            ->with('status', 'Rol creado correctamente y permisos iniciales generados.');
    }

    public function edit(int $id_rol)
    {
        $role = Role::findOrFail($id_rol);
        $roleTypes = $this->roleTypeOptions();

        return view('admin.roles.edit', compact('role', 'roleTypes'));
    }

    public function update(Request $request, int $id_rol)
    {
        $role = Role::findOrFail($id_rol);

        $data = $request->validate([
            'nombre' => 'required|string|max:32|unique:roles,nombre,' . $role->id_rol . ',id_rol',
            'descripcion' => 'nullable|string|max:255',
            'role_type_id' => 'required|exists:role_types,id',
            'home_route' => 'nullable|string|max:64',
            'requires_approval' => 'required|boolean',
            'status' => 'required|boolean',
            'display_order' => 'required|integer|min:0',
        ]);

        $role->update([
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'],
            'role_type_id' => $data['role_type_id'],
            'home_route' => $data['home_route'],
            'requires_approval' => $data['requires_approval'],
            'status' => $data['status'],
            'display_order' => $data['display_order'],
        ]);

        return redirect()->route('admin.roles.index')
            ->with('status', 'Rol actualizado correctamente.');
    }

    public function destroy(int $id_rol)
    {
        $role = Role::findOrFail($id_rol);
        $role->delete();

        return redirect()->route('admin.roles.index')
            ->with('status', 'Rol eliminado correctamente.');
    }

    private function roleTypeOptions(): array
    {
        return RoleType::where('status', true)
            ->orderBy('display_order')
            ->orderBy('type_name')
            ->pluck('type_name', 'id')
            ->all();
    }
}
