<?php

namespace App\Infrastructure\Persistence\Models;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderServiceRole extends Model
{
    use HasFactory;

    protected $table = 'provider_service_roles';

    protected $fillable = [
        'id_rol',
        'provider_service_id',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
        'id_rol' => 'integer',
        'provider_service_id' => 'integer',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'id_rol', 'id_rol');
    }

    public function providerService(): BelongsTo
    {
        return $this->belongsTo(ProviderService::class, 'provider_service_id');
    }
}
