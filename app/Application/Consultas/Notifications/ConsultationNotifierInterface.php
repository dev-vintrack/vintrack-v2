<?php

namespace App\Application\Consultas\Notifications;

use App\Domain\Consultas\Entities\Consultation;

interface ConsultationNotifierInterface
{
    /**
     * Envia la alerta de robo/fraude/recuperado al usuario, con copia oculta
     * al administrador. Solo debe invocarse para consultas del proveedor Placas.
     */
    public function sendPlacasTheftAlert(int $userId, int $providerServiceId, Consultation $consultation): void;
}
