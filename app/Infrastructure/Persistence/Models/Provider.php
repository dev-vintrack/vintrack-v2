<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Provider extends Model
{
    use HasFactory;

    protected $table = 'providers';

    protected $fillable = [
        'code',
        'name',
        'base_url',
        'policies_json',
        'enabled',
    ];

    protected $casts = [
        'policies_json' => 'array',
        'enabled' => 'boolean',
    ];

    public function services(): HasMany
    {
        return $this->hasMany(ProviderService::class, 'provider_id');
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(UserProviderWallet::class, 'provider_id');
    }
}
