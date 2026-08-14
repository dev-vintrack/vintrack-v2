<?php

namespace App\Application\NotificationCases\Services;

final class NotificationCaseMessageFactory
{
    /** @param array<string, mixed> $payload */
    public function make(string $eventType, array $payload): array
    {
        $caseNumber = isset($payload['case_number']) ? (string) $payload['case_number'] : null;
        $reason = isset($payload['message']) ? trim((string) $payload['message']) : '';

        [$title, $body] = match ($eventType) {
            'CASE_CREATED' => ['Expediente asignado', 'Se creó un expediente de notificación para tu consulta.'],
            'CASE_SUBMITTED' => ['Expediente enviado', 'Tu expediente fue enviado correctamente para revisión.'],
            'CASE_RESUBMITTED' => ['Expediente reenviado', 'Tu expediente corregido fue reenviado para revisión.'],
            'CASE_REVIEW_STARTED' => ['Revisión iniciada', 'El análisis documental de tu expediente ha comenzado.'],
            'CASE_REJECTED' => ['Corrección requerida', 'Tu expediente requiere correcciones. Motivo: '.($reason !== '' ? $reason : 'Consulta el detalle del expediente.')],
            'CASE_VALIDATED' => ['Expediente validado', 'Tu expediente documental fue validado.'],
            'CASE_AUTO_CLOSED' => ['Expediente cerrado', 'Tu expediente fue cerrado por falta de seguimiento.'],
            'DEADLINE_REMINDER_T_MINUS_1_DAY' => ['Plazo próximo a vencer', 'El plazo para completar la notificación vence pronto.'],
            'DEADLINE_REACHED' => ['Plazo de notificación vencido', 'El plazo para completar la notificación ha llegado a su límite.'],
            default => ['Actualización de expediente', 'Hay una actualización disponible en tu expediente.'],
        };

        return [
            'title' => $title,
            'body' => $body,
            'subject' => 'VINTrack: '.$title,
            'case_number' => $caseNumber,
        ];
    }
}
