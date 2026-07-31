<?php

namespace App\Infrastructure\Notifications;

use App\Application\Consultas\Notifications\ConsultationNotifierInterface;
use App\Application\Notifications\Services\CustomerMailNotificationService;
use App\Domain\Consultas\Entities\Consultation;

class MailConsultationNotifier implements ConsultationNotifierInterface
{
    public function __construct(
        private readonly CustomerMailNotificationService $notifications
    ) {
    }

    public function sendPlacasTheftAlert(int $userId, int $providerServiceId, Consultation $consultation): void
    {
        $this->notifications->notifyRiskAlert($userId, $providerServiceId, $consultation);
    }
}
