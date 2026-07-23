<?php

namespace App\Presentation\Http\Controllers\Web\Admin;

use App\Infrastructure\Persistence\Models\ProviderService;
use App\Infrastructure\Persistence\Models\ProviderServiceRole;
use App\Models\Role;
use Illuminate\Http\Request;

class AdminProviderServiceRoleController
{
    public function index()
    {
        $roles = Role::where('status', true)
            ->orderBy('display_order')
            ->orderBy('nombre')
            ->get();

        $services = ProviderService::with('provider')
            ->where('enabled', true)
            ->orderBy('name')
            ->get();

        $rolePermissions = ProviderServiceRole::whereIn('id_rol', $roles->pluck('id_rol'))
            ->whereIn('provider_service_id', $services->pluck('id'))
            ->get()
            ->groupBy('id_rol')
            ->map(fn ($items) => $items->pluck('status', 'provider_service_id')->all())
            ->all();

        return view('admin.provider-service-roles.index', compact('roles', 'services', 'rolePermissions'));
    }

    public function update(Request $request, int $id_rol, int $provider_service_id)
    {
        $data = $request->validate([
            'status' => 'required|boolean',
        ]);

        $role = Role::findOrFail($id_rol);
        $service = ProviderService::findOrFail($provider_service_id);

        ProviderServiceRole::updateOrCreate(
            [
                'id_rol' => $role->id_rol,
                'provider_service_id' => $service->id,
            ],
            ['status' => $data['status']]
        );

        return redirect()->route('admin.provider-service-roles.index')
            ->with('status', 'Permiso de servicio actualizado correctamente.');
    }
}
