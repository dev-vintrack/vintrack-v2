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
        'min_price_package',
        'max_price_package',
        'step_price_package',
        'min_validity_days',
        'max_validity_days',
        'step_validity_input',
        'package_blocks_direct_credit',
        'notification_case_deadline_days',
        'notification_case_max_open_days',
        'notification_case_reuse_days',
        'notification_case_max_pending',
        'notification_case_max_files',
        'notification_case_max_file_bytes',
        'notification_case_timezone',
        'notification_case_reservation_ttl_seconds',
    ];

    protected $casts = [
        'min_purchase_user' => 'decimal:2',
        'max_purchase_user' => 'decimal:2',
        'step_purchase_input' => 'decimal:2',
        'min_price_package' => 'decimal:2',
        'max_price_package' => 'decimal:2',
        'step_price_package' => 'decimal:2',
        'min_validity_days' => 'integer',
        'max_validity_days' => 'integer',
        'step_validity_input' => 'integer',
        'package_blocks_direct_credit' => 'boolean',
        'notification_case_deadline_days' => 'integer',
        'notification_case_max_open_days' => 'integer',
        'notification_case_reuse_days' => 'integer',
        'notification_case_max_pending' => 'integer',
        'notification_case_max_files' => 'integer',
        'notification_case_max_file_bytes' => 'integer',
        'notification_case_reservation_ttl_seconds' => 'integer',
    ];

    public static function settings(): self
    {
        return static::first() ?? static::create([
            'min_purchase_user' => 10,
            'max_purchase_user' => 1000,
            'step_purchase_input' => 10,
            'min_price_package' => 100,
            'max_price_package' => 5000,
            'step_price_package' => 100,
            'min_validity_days' => 30,
            'max_validity_days' => 360,
            'step_validity_input' => 30,
            'package_blocks_direct_credit' => true,
            'notification_case_deadline_days' => 3,
            'notification_case_max_open_days' => 30,
            'notification_case_reuse_days' => 90,
            'notification_case_max_pending' => 3,
            'notification_case_max_files' => 8,
            'notification_case_max_file_bytes' => 3145728,
            'notification_case_timezone' => 'America/Mexico_City',
            'notification_case_reservation_ttl_seconds' => 150,
        ]);
    }
}
