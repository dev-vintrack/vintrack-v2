<?php

namespace App\Presentation\Http\Controllers\Web\Admin;

use App\Models\RoleType;
use Illuminate\Http\Request;

class AdminRoleTypeController
{
    public function index()
    {
        $roleTypes = RoleType::orderBy('display_order')
            ->orderBy('type_name')
            ->get();

        return view('admin.role_types.index', compact('roleTypes'));
    }

    public function create()
    {
        return view('admin.role_types.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type_name' => 'required|string|max:32|unique:role_types,type_name',
            'description' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:32',
            'is_admin' => 'required|boolean',
            'is_customer' => 'required|boolean',
            'status' => 'required|boolean',
            'display_order' => 'required|integer|min:0',
        ]);

        RoleType::create($data);

        return redirect()->route('admin.role-types.index')->with('status', 'Tipo de rol creado correctamente.');
    }

    public function edit(RoleType $roleType)
    {
        return view('admin.role_types.edit', compact('roleType'));
    }

    public function update(Request $request, RoleType $roleType)
    {
        $data = $request->validate([
            'type_name' => 'required|string|max:32|unique:role_types,type_name,' . $roleType->id,
            'description' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:32',
            'is_admin' => 'required|boolean',
            'is_customer' => 'required|boolean',
            'status' => 'required|boolean',
            'display_order' => 'required|integer|min:0',
        ]);

        $roleType->update($data);

        return redirect()->route('admin.role-types.index')->with('status', 'Tipo de rol actualizado correctamente.');
    }

    public function destroy(RoleType $roleType)
    {
        if ($roleType->roles()->where('status', true)->exists()) {
            return redirect()->route('admin.role-types.index')
                ->with('status', 'No puedes eliminar un tipo de rol si existe al menos un rol activo que lo use.');
        }

        $roleType->delete();

        return redirect()->route('admin.role-types.index')->with('status', 'Tipo de rol eliminado correctamente.');
    }
}
