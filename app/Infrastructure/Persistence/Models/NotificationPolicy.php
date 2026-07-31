<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPolicy extends Model
{
    public const LOW_BALANCE = 'wallet.low_balance';
    public const ZERO_BALANCE = 'wallet.zero_balance';
    public const EXPIRING = 'wallet.expiring';
    public const EXPIRED = 'wallet.expired';
    public const RISK_ALERT = 'consultation.risk_alert';

    public const EVENTS = [
        self::LOW_BALANCE,
        self::ZERO_BALANCE,
        self::EXPIRING,
        self::EXPIRED,
        self::RISK_ALERT,
    ];

    protected $fillable = [
        'provider_service_id',
        'event_type',
        'enabled',
        'low_balance_threshold',
        'expiring_days',
        'cooldown_hours',
        'bcc_email',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'low_balance_threshold' => 'decimal:2',
        'expiring_days' => 'array',
        'cooldown_hours' => 'integer',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(ProviderService::class, 'provider_service_id');
    }

    public static function defaultsFor(ProviderService $service, string $event): array
    {
        return [
            'enabled' => true,
            'low_balance_threshold' => $event === self::LOW_BALANCE
                ? (float) ($service->min_alert_client ?? config('vintrack.low_credit_threshold', 5))
                : null,
            'expiring_days' => $event === self::EXPIRING ? [7, 3, 1] : null,
            'cooldown_hours' => $event === self::LOW_BALANCE ? 72 : 0,
            'bcc_email' => null,
        ];
    }

    public static function resolveFor(ProviderService $service, string $event): self
    {
        return self::firstOrCreate(
            ['provider_service_id' => $service->id, 'event_type' => $event],
            self::defaultsFor($service, $event)
        );
    }

    public static function createDefaultsFor(ProviderService $service): void
    {
        foreach (self::EVENTS as $event) {
            self::resolveFor($service, $event);
        }
    }

    public static function labels(): array
    {
        return [
            self::LOW_BALANCE => 'Saldo bajo',
            self::ZERO_BALANCE => 'Saldo agotado',
            self::EXPIRING => 'Próximo vencimiento',
            self::EXPIRED => 'Créditos vencidos',
            self::RISK_ALERT => 'Alerta de riesgo',
        ];
    }
}
