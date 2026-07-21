<?php

namespace App\Application\Inventory\Services;

use App\Infrastructure\Persistence\Models\InventoryMovement;
use App\Infrastructure\Persistence\Models\ProviderService;
use App\Infrastructure\Persistence\Models\PurchaseItem;
use App\Infrastructure\Persistence\Models\UserProviderWallet;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventoryMovementService
{
    public function adjust(
        int $providerServiceId,
        float $quantity,
        string $type,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $adminId = null,
        ?string $notes = null
    ): InventoryMovement {
        return DB::transaction(function () use ($providerServiceId, $quantity, $type, $referenceType, $referenceId, $adminId, $notes) {
            $service = ProviderService::lockForUpdate()->find($providerServiceId);

            if (! $service) {
                throw new RuntimeException('Servicio no encontrado.');
            }

            $newBalance = (float) $service->available_credits + $quantity;
            if ($newBalance < 0) {
                throw new RuntimeException('El movimiento dejaría el inventario negativo.');
            }

            $service->available_credits = $newBalance;
            $service->save();

            return InventoryMovement::create([
                'provider_service_id' => $providerServiceId,
                'type' => $type,
                'quantity' => $quantity,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'admin_id' => $adminId,
                'notes' => $notes,
            ]);
        });
    }

    public function purchase(int $providerServiceId, float $quantity, int $purchaseItemId, ?int $adminId = null, ?string $notes = null): InventoryMovement
    {
        return $this->adjust(
            $providerServiceId,
            $quantity,
            InventoryMovement::TYPE_PURCHASE,
            PurchaseItem::class,
            $purchaseItemId,
            $adminId,
            $notes
        );
    }

    public function sale(int $providerServiceId, float $quantity, string $referenceType, int $referenceId, ?int $adminId = null, ?string $notes = null): InventoryMovement
    {
        return $this->adjust(
            $providerServiceId,
            -$quantity,
            InventoryMovement::TYPE_SALE,
            $referenceType,
            $referenceId,
            $adminId,
            $notes
        );
    }

    public function returnExpired(int $providerServiceId, float $quantity, int $walletId, ?int $adminId = null, ?string $notes = null): InventoryMovement
    {
        return $this->adjust(
            $providerServiceId,
            $quantity,
            InventoryMovement::TYPE_EXPIRY_RETURN,
            UserProviderWallet::class,
            $walletId,
            $adminId,
            $notes
        );
    }

    public function manualAdjustment(int $providerServiceId, float $quantity, int $adminId, string $notes): InventoryMovement
    {
        return $this->adjust(
            $providerServiceId,
            $quantity,
            InventoryMovement::TYPE_ADJUSTMENT,
            null,
            null,
            $adminId,
            $notes
        );
    }
}
