<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GlobalConfiguration extends Model
{
    use HasFactory;

    protected $table = 'global_configuration';

    protected $fillable = [
        'min_purchase_user',
        'max_purchase_user',
        'step_purchase_input',
        'min_validity_days',
        'max_validity_days',
        'step_validity_input',
        'package_blocks_direct_credit',
    ];

    protected $casts = [
        'min_purchase_user'            => 'decimal:2',
        'max_purchase_user'            => 'decimal:2',
        'step_purchase_input'          => 'decimal:2',
        'min_validity_days'            => 'integer',
        'max_validity_days'            => 'integer',
        'step_validity_input'          => 'integer',
        'package_blocks_direct_credit' => 'boolean',
    ];

    public static function settings(): self
    {
        return static::first() ?? static::create([
            'min_purchase_user'           => 10,
            'max_purchase_user'           => 1000,
            'step_purchase_input'         => 10,
            'min_validity_days'           => 30,
            'max_validity_days'           => 360,
            'step_validity_input'         => 30,
            'package_blocks_direct_credit' => true,
        ]);
    }
}
