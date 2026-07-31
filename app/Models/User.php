<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'nombre',
        'telefono',
        'id_rol',
        'rol',
        'activo',
        'approved_at',
        'status',
        'email_otp',
        'email_otp_expire',
        'es_oficial',
        'entidad',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_otp_expire' => 'datetime',
            'approved_at' => 'datetime',
            'activo' => 'boolean',
            'es_oficial' => 'boolean',
            'id_rol' => 'integer',
            'password' => 'hashed',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'id_rol', 'id_rol');
    }

    public function getRolAttribute(): ?string
    {
        return $this->role?->nombre;
    }

    public function setRolAttribute(?string $value): void
    {
        if ($value === null) {
            $this->attributes['id_rol'] = null;
            return;
        }

        $role = Role::firstOrCreateByName($value);
        $this->attributes['id_rol'] = $role?->id_rol;
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(\App\Infrastructure\Persistence\Models\Consultation::class, 'user_id');
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(\App\Infrastructure\Persistence\Models\UserProviderWallet::class, 'user_id');
    }

    public function packages(): HasMany
    {
        return $this->hasMany(\App\Infrastructure\Persistence\Models\UserPackage::class, 'user_id');
    }
}
