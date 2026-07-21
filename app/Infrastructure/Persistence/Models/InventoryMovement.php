<?php

namespace App\Infrastructure\Persistence\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    use HasFactory;

    protected $table = 'inventory_movements';

    protected $fillable = [
        'provider_service_id',
        'type',
        'quantity',
        'reference_type',
        'reference_id',
        'admin_id',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public const TYPE_PURCHASE = 'purchase';
    public const TYPE_SALE = 'sale';
    public const TYPE_EXPIRY_RETURN = 'expiry_return';
    public const TYPE_ADJUSTMENT = 'adjustment';

    public function service(): BelongsTo
    {
        return $this->belongsTo(ProviderService::class, 'provider_service_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
