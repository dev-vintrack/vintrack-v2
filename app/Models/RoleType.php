<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoleType extends Model
{
    /** @use HasFactory<\Database\Factories\RoleTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'type_name',
        'description',
        'color',
        'is_admin',
        'is_customer',
        'status',
        'display_order',
    ];

    protected $casts = [
        'is_admin' => 'boolean',
        'is_customer' => 'boolean',
        'status' => 'boolean',
        'display_order' => 'integer',
    ];

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class, 'role_type_id');
    }
}
