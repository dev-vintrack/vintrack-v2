<?php

namespace App\Infrastructure\Persistence\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Consultation extends Model
{
    use HasFactory;

    protected $table = 'consultations';

    protected $fillable = [
        'user_id',
        'provider_id',
        'provider_service_id',
        'criterio',
        'valor',
        'api_id',
        'services',
        'costo_credito',
        'http_status_post',
        'http_status_get',
        'success',
        'error_message',
        'alerta_robo',
        'repuve_robo',
        'pgj_robo',
        'ocra_robo',
        'carfax_robo',
        'rapi_robo',
        'flags_json',
        'response_json',
        'credits_api',
    ];

    protected $casts = [
        'services' => 'array',
        'costo_credito' => 'decimal:2',
        'success' => 'boolean',
        'alerta_robo' => 'boolean',
        'repuve_robo' => 'boolean',
        'pgj_robo' => 'boolean',
        'ocra_robo' => 'boolean',
        'carfax_robo' => 'boolean',
        'rapi_robo' => 'boolean',
        'flags_json' => 'array',
        'response_json' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    public function providerService(): BelongsTo
    {
        return $this->belongsTo(ProviderService::class, 'provider_service_id');
    }
}
