<?php

namespace App\Infrastructure\Persistence\Models;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProviderService extends Model
{
    use HasFactory;

    protected $table = 'provider_services';

    protected $fillable = [
        'provider_id',
        'key',
        'name',
        'credit_cost',
        'available_credits',
        'min_alert_client',
        'min_alert_admin',
        'enabled',
    ];

    protected $casts = [
        'credit_cost' => 'decimal:2',
        'available_credits' => 'decimal:2',
        'min_alert_client' => 'decimal:2',
        'min_alert_admin' => 'decimal:2',
        'enabled' => 'boolean',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(ProviderServiceSection::class, 'provider_service_id');
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class, 'provider_service_id');
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'provider_service_id');
    }

    public function providerServiceRoles(): HasMany
    {
        return $this->hasMany(ProviderServiceRole::class, 'provider_service_id');
    }

    protected static function booted(): void
    {
        static::created(function (ProviderService $service) {
            foreach (Role::all() as $role) {
                ProviderServiceRole::firstOrCreate(
                    [
                        'id_rol' => $role->id_rol,
                        'provider_service_id' => $service->id,
                    ],
                    ['status' => true]
                );
            }
        });
    }
}
