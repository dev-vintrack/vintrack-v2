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
        'provider_service_id',
        'balance',
        'min_alert',
        'validity_start',
        'validity_end',
        'status',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'min_alert' => 'decimal:2',
        'validity_start' => 'datetime',
        'validity_end' => 'datetime',
    ];

    public static function syncExpiredStatuses(): void
    {
        static::where('status', 'active')
            ->whereNotNull('validity_end')
            ->where('validity_end', '<=', now())
            ->update(['status' => 'expired']);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(ProviderService::class, 'provider_service_id');
    }

    public function ledger(): HasMany
    {
        return $this->hasMany(WalletLedgerEntry::class, 'wallet_id');
    }
}
