<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminMenuPermission extends Model
{
    use HasFactory;

    protected $table = 'admin_menu_permissions';

    protected $fillable = [
        'role',
        'route_name',
        'label',
        'icon',
        'enabled',
        'display_order',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'display_order' => 'integer',
    ];

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }
}
