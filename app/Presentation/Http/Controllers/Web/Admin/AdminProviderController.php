<?php

namespace App\Presentation\Http\Controllers\Web\Admin;

use App\Infrastructure\Persistence\Models\Provider;
use App\Infrastructure\Persistence\Models\ProviderService;
use App\Infrastructure\Persistence\Models\ProviderServiceSection;
use App\Infrastructure\Persistence\Models\ProviderServiceSectionRole;
use App\Presentation\Support\RoleHelper;
use Illuminate\Http\Request;

class AdminProviderController
{
    public function index()
    {
        $providers = Provider::with(['services.sections.roleSettings'])->get();
        $roles = RoleHelper::ROLES;

        return view('admin.providers.index', compact('providers', 'roles'));
    }

    public function updateProvider(Request $request, int $id)
    {
        $data = $request->validate([
            'enabled' => 'required|boolean',
        ]);

        $provider = Provider::findOrFail($id);
        $provider->enabled = $data['enabled'];
        $provider->save();

        return redirect()->route('admin.providers.index')->with('status', 'Proveedor actualizado.');
    }

    public function updateService(Request $request, int $providerId, int $serviceId)
    {
        $data = $request->validate([
            'enabled' => 'required|boolean',
            'credit_cost' => 'required|numeric|min:0',
        ]);

        $service = ProviderService::where('provider_id', $providerId)
            ->where('id', $serviceId)
            ->firstOrFail();

        $service->enabled = $data['enabled'];
        $service->credit_cost = $data['credit_cost'];
        $service->save();

        return redirect()->route('admin.providers.index')->with('status', 'Servicio actualizado.');
    }

    public function updateSection(Request $request, int $providerId, int $serviceId, int $sectionId)
    {
        $data = $request->validate([
            'status' => 'required|boolean',
        ]);

        $section = ProviderServiceSection::where('provider_service_id', $serviceId)
            ->where('id', $sectionId)
            ->firstOrFail();

        $section->status = $data['status'];
        $section->save();

        return redirect()->route('admin.providers.index')
            ->with('status', 'Sección ' . $section->section_code . ' actualizada.');
    }

    public function updateSectionRole(Request $request, int $providerId, int $serviceId, int $sectionId, string $role)
    {
        $data = $request->validate([
            'status' => 'required|boolean',
        ]);

        if (! array_key_exists($role, RoleHelper::ROLES)) {
            abort(404, 'Rol no válido.');
        }

        $section = ProviderServiceSection::where('provider_service_id', $serviceId)
            ->where('id', $sectionId)
            ->firstOrFail();

        ProviderServiceSectionRole::updateOrCreate(
            [
                'provider_service_section_id' => $section->id,
                'role' => $role,
            ],
            ['status' => $data['status']]
        );

        return redirect()->route('admin.providers.index')
            ->with('status', 'Permiso de rol actualizado.');
    }
}
