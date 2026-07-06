<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletLedgerEntry extends Model
{
    use HasFactory;

    protected $table = 'wallet_ledger';

    public $timestamps = false;

    protected $fillable = [
        'wallet_id',
        'delta',
        'reason',
        'meta',
        'correlation_id',
        'created_at',
    ];

    protected $casts = [
        'delta' => 'decimal:2',
        'meta' => 'array',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(UserProviderWallet::class, 'wallet_id');
    }
}
