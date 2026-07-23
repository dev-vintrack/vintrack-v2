<?php

namespace App\Presentation\Http\Controllers\Web\Admin;

use App\Application\Inventory\Services\InventoryMovementService;
use App\Application\Inventory\Services\ReturnExpiredCreditsService;
use App\Infrastructure\Persistence\Models\InventoryMovement;
use App\Infrastructure\Persistence\Models\ProviderService;
use App\Presentation\Support\RoleHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminInventoryController
{
    public function __construct(
        private readonly InventoryMovementService $inventoryService,
        private readonly ReturnExpiredCreditsService $returnExpiredService
    ) {
    }

    public function index(Request $request)
    {
        $allowedServiceIds = RoleHelper::allowedServiceIds(Auth::user()?->id_rol);

        $services = ProviderService::with('provider')
            ->whereIn('id', $allowedServiceIds)
            ->orderBy('provider_id')
            ->orderBy('name')
            ->get();

        $selectedService = (int) $request->input('provider_service_id');
        $selectedType = $request->input('type');

        $movementsQuery = InventoryMovement::with(['service.provider', 'admin'])
            ->orderBy('created_at', 'desc');

        if ($selectedService) {
            $movementsQuery->where('provider_service_id', $selectedService);
        }

        if ($selectedType) {
            $movementsQuery->where('type', $selectedType);
        }

        $totalMovements = $movementsQuery->count();
        $movements = $movementsQuery->paginate(25)->withQueryString();

        $balances = ProviderService::with('provider')
            ->whereIn('id', $allowedServiceIds)
            ->selectRaw('provider_services.*, (
                SELECT COALESCE(SUM(quantity), 0)
                FROM inventory_movements
                WHERE inventory_movements.provider_service_id = provider_services.id
            ) as inventory_balance')
            ->orderBy('provider_id')
            ->orderBy('name')
            ->get();

        $totalCredits = (float) $balances->sum('available_credits');
        $totalServices = $balances->count();

        return view('admin.inventory.index', compact(
            'services',
            'selectedService',
            'selectedType',
            'movements',
            'balances',
            'totalMovements',
            'totalCredits',
            'totalServices'
        ));
    }

    public function storeAdjustment(Request $request)
    {
        $allowedServiceIds = RoleHelper::allowedServiceIds(Auth::user()?->id_rol);

        $data = $request->validate([
            'provider_service_id' => ['required', 'exists:provider_services,id', 'in:' . implode(',', $allowedServiceIds)],
            'quantity' => 'required|numeric|not_in:0',
            'notes' => 'required|string|max:500',
        ]);

        $this->inventoryService->manualAdjustment(
            (int) $data['provider_service_id'],
            (float) $data['quantity'],
            Auth::id(),
            $data['notes']
        );

        return redirect()->route('admin.inventory.index')->with('status', 'Ajuste registrado correctamente.');
    }

    public function returnExpired()
    {
        $result = $this->returnExpiredService->run();

        $message = "Reintegración completada: {$result['processed']} wallets, " . number_format($result['returned'], 2) . " créditos.";
        if ($result['errors'] > 0) {
            $message .= " Errores: {$result['errors']}.";
        }

        return redirect()->route('admin.inventory.index')->with('status', $message);
    }
}
