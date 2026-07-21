<?php

namespace App\Infrastructure\Persistence\Models;

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
        'enabled',
    ];

    protected $casts = [
        'credit_cost' => 'decimal:2',
        'available_credits' => 'decimal:2',
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
}
