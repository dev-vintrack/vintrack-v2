<?php

namespace App\Infrastructure\Persistence\Models;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminMenuPermission extends Model
{
    use HasFactory;

    protected $table = 'admin_menu_permissions';

    protected $fillable = [
        'id_rol',
        'route_name',
        'enabled',
        'display_order',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'display_order' => 'integer',
        'id_rol' => 'integer',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'id_rol', 'id_rol');
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'route_name', 'route_name');
    }

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    public function scopeForRole($query, int $idRol)
    {
        return $query->where('id_rol', $idRol);
    }
}
