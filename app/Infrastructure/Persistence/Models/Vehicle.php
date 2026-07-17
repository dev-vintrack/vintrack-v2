<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vehicle extends Model
{
    use HasFactory;

    protected $table = 'vehicles';

    protected $fillable = [
        'provider_id',
        'criterio',
        'valor',
        'marca',
        'modelo',
        'anio',
        'ultimo_status_robo',
        'total_consultas',
        'ultima_consulta_at',
    ];

    protected $casts = [
        'ultimo_status_robo' => 'boolean',
        'total_consultas' => 'integer',
        'ultima_consulta_at' => 'datetime',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }
}
