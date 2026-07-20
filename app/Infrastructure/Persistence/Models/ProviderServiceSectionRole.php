<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderServiceSectionRole extends Model
{
    use HasFactory;

    protected $table = 'provider_service_section_roles';

    protected $fillable = [
        'provider_service_section_id',
        'role',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(ProviderServiceSection::class, 'provider_service_section_id');
    }
}
