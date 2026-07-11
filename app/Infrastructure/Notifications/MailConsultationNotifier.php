<?php

namespace App\Infrastructure\Notifications;

use App\Application\Consultas\Notifications\ConsultationNotifierInterface;
use App\Domain\Consultas\Entities\Consultation;
use App\Mail\LowCreditMail;
use App\Mail\PlacasAlertMail;
use App\Models\User;
use App\Presentation\Support\PlacasReportPresenter;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class MailConsultationNotifier implements ConsultationNotifierInterface
{
    public function sendPlacasTheftAlert(int $userId, Consultation $consultation): void
    {
        $user = User::find($userId);
        if (!$user || empty($user->email)) {
            return;
        }

        $sections = PlacasReportPresenter::sections($consultation->responseJson());
        $adminBcc = (string) Config::get('vintrack.admin_email', '');

        $userInfo = [
            'email' => $user->email,
            'nombre' => $user->nombre ?? $user->name ?? '',
            'telefono' => $user->telefono ?? '',
            'rol' => $user->rol ?? '',
        ];

        try {
            Mail::to($user->email)->send(new PlacasAlertMail(
                $userInfo,
                $consultation->criterio(),
                $consultation->valor(),
                $sections,
                $adminBcc !== '' ? $adminBcc : null
            ));
        } catch (Throwable $e) {
            Log::error('Error enviando correo de alerta Placas: ' . $e->getMessage());
        }
    }

    public function sendLowCredit(int $userId, float $balance): void
    {
        $user = User::find($userId);
        if (!$user || empty($user->email)) {
            return;
        }

        try {
            Mail::to($user->email)->send(new LowCreditMail($user->email, $balance));
        } catch (Throwable $e) {
            Log::error('Error enviando correo de créditos bajos: ' . $e->getMessage());
        }
    }
}
