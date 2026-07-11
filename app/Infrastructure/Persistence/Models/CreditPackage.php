<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditPackage extends Model
{
    protected $fillable = ['name', 'description', 'price', 'validity_days', 'active'];

    protected $casts = [
        'price' => 'decimal:2',
        'validity_days' => 'integer',
        'active' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(CreditPackageItem::class);
    }

    public function userPackages(): HasMany
    {
        return $this->hasMany(UserPackage::class);
    }
}
