<?php

namespace App\Domain\NotificationCases\Enums;

enum NotificationCaseStatus: string
{
    case PENDING = 'PENDING';
    case SUBMITTED = 'SUBMITTED';
    case UNDER_REVIEW = 'UNDER_REVIEW';
    case REJECTED = 'REJECTED';
    case VALIDATED = 'VALIDATED';
    case CLOSED_NO_FOLLOW_UP = 'CLOSED_NO_FOLLOW_UP';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'PENDIENTE',
            self::SUBMITTED => 'ENVIADO',
            self::UNDER_REVIEW => 'EN VALIDACIÓN',
            self::REJECTED => 'RECHAZADO',
            self::VALIDATED => 'VALIDADO',
            self::CLOSED_NO_FOLLOW_UP => 'CERRADO POR FALTA DE SEGUIMIENTO',
        };
    }

    public function isPending(): bool
    {
        return ! in_array($this, [self::VALIDATED, self::CLOSED_NO_FOLLOW_UP], true);
    }

    public function isEditableByOwner(): bool
    {
        return in_array($this, [self::PENDING, self::REJECTED], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::VALIDATED, self::CLOSED_NO_FOLLOW_UP], true);
    }
}
