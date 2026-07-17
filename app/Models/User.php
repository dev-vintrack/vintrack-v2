<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        'rol',
        'activo',
        'approved_at',
        'status',
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
            'approved_at' => 'datetime',
            'activo' => 'boolean',
            'password' => 'hashed',
        ];
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
