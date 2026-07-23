<?php

namespace App\Presentation\Http\Controllers\Web\Admin;

use App\Application\Inventory\Services\InventoryMovementService;
use App\Infrastructure\Persistence\Models\Provider;
use App\Infrastructure\Persistence\Models\ProviderService;
use App\Infrastructure\Persistence\Models\PurchaseItem;
use App\Presentation\Support\RoleHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminPurchaseItemController
{
    public function __construct(
        private readonly InventoryMovementService $inventoryService
    ) {
    }

    public function index()
    {
        $purchases = PurchaseItem::with(['provider', 'service.provider', 'admin'])
            ->orderBy('purchase_date', 'desc')
            ->paginate(20);

        $totalPurchases = PurchaseItem::count();
        $activePurchases = PurchaseItem::where('status', 'active')->count();
        $totalCost = (float) PurchaseItem::sum('total_cost');

        return view('admin.purchases.index', compact(
            'purchases',
            'totalPurchases',
            'activePurchases',
            'totalCost'
        ));
    }

    private function allowedServices()
    {
        return ProviderService::with('provider')
            ->where('enabled', true)
            ->whereIn('id', RoleHelper::allowedServiceIds(Auth::user()?->id_rol))
            ->orderBy('provider_id')
            ->orderBy('name')
            ->get();
    }

    public function create()
    {
        $providers = Provider::where('enabled', true)->orderBy('name')->get();
        $services = $this->allowedServices();

        return view('admin.purchases.create', compact('providers', 'services'));
    }

    public function store(Request $request)
    {
        $allowedServiceIds = RoleHelper::allowedServiceIds(Auth::user()?->id_rol);

        $data = $request->validate([
            'provider_id' => 'required|exists:providers,id',
            'provider_service_id' => ['required', 'exists:provider_services,id', 'in:' . implode(',', $allowedServiceIds)],
            'quantity' => 'required|numeric|min:0.01',
            'unit_cost' => 'required|numeric|min:0',
            'purchase_date' => 'required|date',
            'status' => 'required|string|in:active,cancelled',
            'notes' => 'nullable|string|max:500',
        ]);

        $data['admin_id'] = Auth::id();

        $purchase = PurchaseItem::create($data);

        if ($purchase->status === 'active') {
            $this->inventoryService->purchase(
                $purchase->provider_service_id,
                (float) $purchase->quantity,
                $purchase->id,
                Auth::id(),
                $purchase->notes
            );
        }

        return redirect()->route('admin.purchases.index')->with('status', 'Compra registrada correctamente.');
    }

    public function edit(int $id)
    {
        $purchase = PurchaseItem::findOrFail($id);
        $providers = Provider::where('enabled', true)->orderBy('name')->get();
        $services = $this->allowedServices();

        return view('admin.purchases.edit', compact('purchase', 'providers', 'services'));
    }

    public function update(Request $request, int $id)
    {
        $purchase = PurchaseItem::findOrFail($id);
        $allowedServiceIds = RoleHelper::allowedServiceIds(Auth::user()?->id_rol);

        $data = $request->validate([
            'provider_id' => 'required|exists:providers,id',
            'provider_service_id' => ['required', 'exists:provider_services,id', 'in:' . implode(',', $allowedServiceIds)],
            'quantity' => 'required|numeric|min:0.01',
            'unit_cost' => 'required|numeric|min:0',
            'purchase_date' => 'required|date',
            'status' => 'required|string|in:active,cancelled',
            'notes' => 'nullable|string|max:500',
        ]);

        $originalStatus = $purchase->status;
        $originalQuantity = (float) $purchase->quantity;
        $originalServiceId = (int) $purchase->provider_service_id;

        $purchase->update($data);

        $this->reconcileInventory($purchase, $originalStatus, $originalQuantity, $originalServiceId);

        return redirect()->route('admin.purchases.index')->with('status', 'Compra actualizada correctamente.');
    }

    public function destroy(int $id)
    {
        $purchase = PurchaseItem::findOrFail($id);

        if ($purchase->status === 'active') {
            $this->inventoryService->manualAdjustment(
                $purchase->provider_service_id,
                -(float) $purchase->quantity,
                Auth::id(),
                "Eliminación de compra #{$purchase->id}"
            );
        }

        $purchase->delete();

        return redirect()->route('admin.purchases.index')->with('status', 'Compra eliminada.');
    }

    private function reconcileInventory(
        PurchaseItem $purchase,
        string $originalStatus,
        float $originalQuantity,
        int $originalServiceId
    ): void {
        $adminId = Auth::id();

        if ($originalStatus === 'active') {
            $this->inventoryService->manualAdjustment(
                $originalServiceId,
                -$originalQuantity,
                $adminId,
                "Reversión por edición de compra #{$purchase->id}"
            );
        }

        if ($purchase->status === 'active') {
            $this->inventoryService->purchase(
                $purchase->provider_service_id,
                (float) $purchase->quantity,
                $purchase->id,
                $adminId,
                $purchase->notes
            );
        }
    }
}
