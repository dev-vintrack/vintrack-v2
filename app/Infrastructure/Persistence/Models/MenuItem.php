<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    use HasFactory;

    protected $table = 'menu_items';

    protected $fillable = [
        'scope',
        'route_name',
        'label',
        'icon',
        'default_display_order',
    ];

    protected $casts = [
        'default_display_order' => 'integer',
    ];

    public function adminPermissions(): HasMany
    {
        return $this->hasMany(AdminMenuPermission::class, 'route_name', 'route_name');
    }

    public function customerPermissions(): HasMany
    {
        return $this->hasMany(CustomerMenuPermission::class, 'route_name', 'route_name');
    }

    public function scopeAdmin($query)
    {
        return $query->where('scope', 'admin');
    }

    public function scopeCustomer($query)
    {
        return $query->where('scope', 'customer');
    }
}
