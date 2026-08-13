<?php

namespace App\Application\NotificationCases\Exceptions;

use RuntimeException;

final class ConsultationBlockedException extends RuntimeException
{
    public function __construct(public readonly int $pendingCount)
    {
        parent::__construct('Consulta bloqueada: el usuario alcanzó el máximo de expedientes pendientes.');
    }
}
