<?php

namespace App\Infrastructure\Persistence\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    use HasFactory;

    protected $table = 'purchase_items';

    protected $fillable = [
        'provider_id',
        'provider_service_id',
        'quantity',
        'unit_cost',
        'total_cost',
        'purchase_date',
        'status',
        'notes',
        'admin_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'purchase_date' => 'date',
        'status' => 'string',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(ProviderService::class, 'provider_service_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    protected static function booted(): void
    {
        static::saving(function (PurchaseItem $item) {
            $item->total_cost = (float) $item->quantity * (float) $item->unit_cost;
        });
    }
}
