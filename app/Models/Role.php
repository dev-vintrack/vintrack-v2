<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    /** @use HasFactory<\Database\Factories\RoleFactory> */
    use HasFactory;

    protected $primaryKey = 'id_rol';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'nombre',
        'descripcion',
        'role_type_id',
        'home_route',
        'requires_approval',
        'status',
        'display_order',
    ];

    protected $casts = [
        'role_type_id' => 'integer',
        'requires_approval' => 'boolean',
        'status' => 'boolean',
        'display_order' => 'integer',
        'fecha_creacion' => 'datetime',
        'updated_at' => 'datetime',
    ];

    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'updated_at';

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'id_rol', 'id_rol');
    }

    public function roleType(): BelongsTo
    {
        return $this->belongsTo(RoleType::class, 'role_type_id');
    }

    public function menuPermissions(): HasMany
    {
        return $this->hasMany(\App\Infrastructure\Persistence\Models\AdminMenuPermission::class, 'id_rol', 'id_rol');
    }

    public function customerMenuPermissions(): HasMany
    {
        return $this->hasMany(\App\Infrastructure\Persistence\Models\CustomerMenuPermission::class, 'id_rol', 'id_rol');
    }

    public function sectionRoles(): HasMany
    {
        return $this->hasMany(\App\Infrastructure\Persistence\Models\ProviderServiceSectionRole::class, 'id_rol', 'id_rol');
    }

    public function providerServiceRoles(): HasMany
    {
        return $this->hasMany(\App\Infrastructure\Persistence\Models\ProviderServiceRole::class, 'id_rol', 'id_rol');
    }

    public static function idForName(string $name): ?int
    {
        return static::where('nombre', $name)->value('id_rol');
    }

    public static function firstOrCreateByName(string $name): static
    {
        $adminNames = ['admin', 'analista', 'soporte'];
        $approvalNames = ['perito', 'oficial', 'unidad_analisis'];

        return static::firstOrCreate(
            ['nombre' => $name],
            [
                'descripcion' => $name,
                'role_type_id' => self::defaultAdminRoleTypeId($name),
                'home_route' => match ($name) {
                    'cliente_registrado' => 'home.cliente',
                    'perito' => 'home.perito',
                    'oficial' => 'home.oficial',
                    'unidad_analisis' => 'home.unidad_analisis',
                    'ocasional' => 'home.ocasional',
                    default => 'home',
                },
                'requires_approval' => in_array($name, $approvalNames, true),
                'status' => true,
                'display_order' => 99,
            ]
        );
    }

    private static function defaultAdminRoleTypeId(?string $name): ?int
    {
        if ($name === null) {
            return null;
        }

        $adminNames = ['admin', 'analista', 'soporte'];
        $typeName = in_array($name, $adminNames, true) ? 'Administrativo' : 'Cliente';

        $type = RoleType::where('type_name', $typeName)->first();

        return $type?->id;
    }
}
