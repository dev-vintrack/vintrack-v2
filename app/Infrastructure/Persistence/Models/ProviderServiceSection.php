<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProviderServiceSection extends Model
{
    use HasFactory;

    protected $table = 'provider_services_sections';

    protected $fillable = [
        'provider_service_id',
        'section_code',
        'section_name',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(ProviderService::class, 'provider_service_id');
    }

    public function roleSettings(): HasMany
    {
        return $this->hasMany(ProviderServiceSectionRole::class, 'provider_service_section_id');
    }

    public function isActiveForRole(string $role): bool
    {
        $setting = $this->roleSettings()->where('role', $role)->first();

        return $setting ? $setting->status : $this->status;
    }
}
