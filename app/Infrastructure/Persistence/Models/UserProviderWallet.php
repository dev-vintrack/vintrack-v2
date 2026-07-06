<?php

namespace App\Infrastructure\Persistence\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserProviderWallet extends Model
{
    use HasFactory;

    protected $table = 'user_provider_wallets';

    protected $fillable = [
        'user_id',
        'provider_id',
        'balance',
        'min_alert',
        'validity_start',
        'validity_end',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'min_alert' => 'decimal:2',
        'validity_start' => 'datetime',
        'validity_end' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    public function ledger(): HasMany
    {
        return $this->hasMany(WalletLedgerEntry::class, 'wallet_id');
    }
}
