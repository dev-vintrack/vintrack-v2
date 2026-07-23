<?php

namespace App\Infrastructure\Persistence\Models;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderServiceSectionRole extends Model
{
    use HasFactory;

    protected $table = 'provider_service_section_roles';

    protected $fillable = [
        'provider_service_section_id',
        'id_rol',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
        'id_rol' => 'integer',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(ProviderServiceSection::class, 'provider_service_section_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'id_rol', 'id_rol');
    }
}
