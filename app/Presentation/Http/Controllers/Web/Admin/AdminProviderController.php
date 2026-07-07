<?php

namespace App\Presentation\Http\Controllers\Web\Admin;

use App\Infrastructure\Persistence\Models\Provider;
use App\Infrastructure\Persistence\Models\ProviderService;
use Illuminate\Http\Request;

class AdminProviderController
{
    public function index()
    {
        $providers = Provider::with('services')->get();

        return view('admin.providers.index', compact('providers'));
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
}
