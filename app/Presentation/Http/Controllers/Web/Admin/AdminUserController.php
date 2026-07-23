<?php

namespace App\Presentation\Http\Controllers\Web\Admin;

use App\Models\Role;
use App\Models\User;
use App\Presentation\Support\RoleHelper;
use Illuminate\Http\Request;

class AdminUserController
{
    public function index()
    {
        $users = User::with('role.roleType')->orderBy('created_at', 'desc')->get();
        $roles = Role::pluck('descripcion', 'nombre')->all();
        $kpis = $this->buildUserKpis($users);

        return view('admin.users.index', compact('users', 'roles', 'kpis'));
    }

    private function buildUserKpis($users): array
    {
        return [
            'total' => $users->count(),
            'active' => $users->where('activo', true)->count(),
            'pending' => $users->where('status', 'pending')->count(),
            'admins' => $users->filter(fn ($user) => RoleHelper::isAdmin($user))->count(),
        ];
    }

    public function edit(int $id)
    {
        $user  = User::findOrFail($id);
        $roles = Role::pluck('descripcion', 'nombre')->all();

        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, int $id)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:128',
            'email'    => 'required|string|email|max:255|unique:users,email,' . $id,
            'telefono' => 'required|string|max:32',
            'rol'      => 'required|exists:roles,nombre',
            'activo'   => 'required|boolean',
            'status'   => 'required|in:active,pending',
        ]);

        $user = User::findOrFail($id);

        $requiresApproval = RoleHelper::requiresApproval($data['rol']);
        $status = $data['status'];

        if (! $requiresApproval && $status === 'pending') {
            $status = 'active';
        }

        $user->update([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'telefono'  => $data['telefono'],
            'rol'       => $data['rol'],
            'activo'    => $data['activo'],
            'status'    => $status,
            'approved_at' => $status === 'active' && $requiresApproval ? now() : $user->approved_at,
        ]);

        return redirect()->route('admin.users.index')->with('status', 'Usuario actualizado correctamente.');
    }

    public function approve(int $id)
    {
        $user = User::findOrFail($id);

        if (! RoleHelper::requiresApproval($user->rol)) {
            return redirect()->route('admin.users.index')->with('status', 'Este rol no requiere aprobación.');
        }

        $user->update([
            'status'      => 'active',
            'activo'      => true,
            'approved_at' => now(),
        ]);

        return redirect()->route('admin.users.index')->with('status', 'Solicitud aprobada correctamente.');
    }

    public function destroy(int $id)
    {
        User::findOrFail($id)->delete();

        return redirect()->route('admin.users.index')->with('status', 'Usuario eliminado.');
    }
}
