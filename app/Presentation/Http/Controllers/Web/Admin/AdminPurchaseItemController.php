<?php

namespace App\Presentation\Http\Controllers\Web\Admin;

use App\Infrastructure\Persistence\Models\Provider;
use App\Infrastructure\Persistence\Models\ProviderService;
use App\Infrastructure\Persistence\Models\PurchaseItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminPurchaseItemController
{
    public function index()
    {
        $purchases = PurchaseItem::with(['provider', 'service.provider', 'admin'])
            ->orderBy('purchase_date', 'desc')
            ->paginate(20);

        return view('admin.purchases.index', compact('purchases'));
    }

    public function create()
    {
        $providers = Provider::where('enabled', true)->orderBy('name')->get();
        $services = ProviderService::with('provider')
            ->where('enabled', true)
            ->orderBy('provider_id')
            ->orderBy('name')
            ->get();

        return view('admin.purchases.create', compact('providers', 'services'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'provider_id' => 'required|exists:providers,id',
            'provider_service_id' => 'required|exists:provider_services,id',
            'quantity' => 'required|numeric|min:0.01',
            'unit_cost' => 'required|numeric|min:0',
            'purchase_date' => 'required|date',
            'status' => 'required|string|in:active,cancelled',
            'notes' => 'nullable|string|max:500',
        ]);

        $data['admin_id'] = Auth::id();

        $purchase = PurchaseItem::create($data);

        $this->updateInventory($purchase);

        return redirect()->route('admin.purchases.index')->with('status', 'Compra registrada correctamente.');
    }

    public function edit(int $id)
    {
        $purchase = PurchaseItem::findOrFail($id);
        $providers = Provider::where('enabled', true)->orderBy('name')->get();
        $services = ProviderService::with('provider')
            ->where('enabled', true)
            ->orderBy('provider_id')
            ->orderBy('name')
            ->get();

        return view('admin.purchases.edit', compact('purchase', 'providers', 'services'));
    }

    public function update(Request $request, int $id)
    {
        $purchase = PurchaseItem::findOrFail($id);

        $data = $request->validate([
            'provider_id' => 'required|exists:providers,id',
            'provider_service_id' => 'required|exists:provider_services,id',
            'quantity' => 'required|numeric|min:0.01',
            'unit_cost' => 'required|numeric|min:0',
            'purchase_date' => 'required|date',
            'status' => 'required|string|in:active,cancelled',
            'notes' => 'nullable|string|max:500',
        ]);

        $originalStatus = $purchase->status;
        $originalQuantity = (float) $purchase->quantity;
        $originalServiceId = $purchase->provider_service_id;

        $purchase->update($data);

        $this->updateInventory($purchase, $originalStatus, $originalQuantity, $originalServiceId);

        return redirect()->route('admin.purchases.index')->with('status', 'Compra actualizada correctamente.');
    }

    public function destroy(int $id)
    {
        $purchase = PurchaseItem::findOrFail($id);

        $this->revertInventory($purchase);

        $purchase->delete();

        return redirect()->route('admin.purchases.index')->with('status', 'Compra eliminada.');
    }

    private function updateInventory(
        PurchaseItem $purchase,
        ?string $originalStatus = null,
        ?float $originalQuantity = null,
        ?int $originalServiceId = null
    ): void {
        $service = ProviderService::find($purchase->provider_service_id);
        if (! $service) {
            return;
        }

        if ($originalServiceId !== null && $originalServiceId !== $purchase->provider_service_id) {
            $originalService = ProviderService::find($originalServiceId);
            if ($originalService && $originalStatus === 'active') {
                $originalService->available_credits = max(0, (float) $originalService->available_credits - $originalQuantity);
                $originalService->save();
            }
            if ($purchase->status === 'active') {
                $service->available_credits = (float) $service->available_credits + (float) $purchase->quantity;
                $service->save();
            }
            return;
        }

        if ($originalStatus !== null && $originalStatus !== $purchase->status) {
            if ($purchase->status === 'active') {
                $service->available_credits = (float) $service->available_credits + (float) $purchase->quantity;
            } else {
                $service->available_credits = max(0, (float) $service->available_credits - (float) $purchase->quantity);
            }
            $service->save();
            return;
        }

        if ($originalQuantity !== null && $originalStatus === 'active' && $purchase->status === 'active') {
            $delta = (float) $purchase->quantity - $originalQuantity;
            $service->available_credits = max(0, (float) $service->available_credits + $delta);
            $service->save();
        }
    }

    private function revertInventory(PurchaseItem $purchase): void
    {
        if ($purchase->status !== 'active') {
            return;
        }

        $service = ProviderService::find($purchase->provider_service_id);
        if (! $service) {
            return;
        }

        $service->available_credits = max(0, (float) $service->available_credits - (float) $purchase->quantity);
        $service->save();
    }
}
