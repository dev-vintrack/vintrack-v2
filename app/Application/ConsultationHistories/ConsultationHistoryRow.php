<?php

namespace App\Application\ConsultationHistories;

use App\Domain\NotificationCases\Enums\NotificationCaseStatus;
use stdClass;

final class ConsultationHistoryRow
{
    public static function fromDatabase(stdClass $row, ?int $viewerUserId): array
    {
        $relation = $row->applicable_case_id === null
            ? 'NO_CASE'
            : ((int) $row->case_owner_user_id === (int) ($viewerUserId ?? $row->case_owner_user_id) ? 'OWN_CASE' : 'OTHER_USER_CASE');
        $notified = in_array($row->general_status, ['SUBMITTED', 'UNDER_REVIEW', 'REJECTED', 'VALIDATED'], true)
            || ($row->general_status === 'CLOSED_NO_FOLLOW_UP' && $row->first_submitted_at !== null);
        $validated = $row->general_status === 'VALIDATED';
        $action = self::action($row, $relation, $viewerUserId === null);

        return [
            'consultation_id' => (int) $row->consultation_id,
            'consulted_at' => $row->consulted_at,
            'user' => $viewerUserId === null ? ['name' => $row->user_name, 'email' => $row->user_email] : null,
            'vin' => $row->vin === 'VIN_NOT_AVAILABLE' ? 'VIN NO DISPONIBLE' : $row->vin,
            'license_plate' => $row->license_plate ?: '—',
            'make' => $row->make ?: '—', 'model' => $row->model ?: '—', 'model_year' => $row->model_year ?: '—',
            'service' => $row->service_name ?: '—', 'theft_status' => $row->theft_status,
            'case_relation' => $relation, 'notified_status' => $notified ? 'SI' : 'NO',
            'validated_status' => $validated ? 'SI' : 'NO',
            'notification_deadline' => $row->notification_deadline_at,
            'general_status' => $row->general_status ?: 'NO APLICA',
            'case_message' => $relation === 'OTHER_USER_CASE' ? 'EXPEDIENTE DE NOTIFICACION EN PROCESO POR OTRO USUARIO' : null,
            'action' => $action,
        ];
    }

    private static function action(stdClass $row, string $relation, bool $admin): ?array
    {
        if ($row->applicable_case_id === null || $relation === 'OTHER_USER_CASE') {
            return null;
        }
        if ($admin) {
            return ['label' => 'VER EXPEDIENTE', 'url' => route('admin.notification-cases.show', $row->applicable_case_id)];
        }
        $status = $row->general_status ? NotificationCaseStatus::from($row->general_status) : null;
        $label = match ($status) {
            NotificationCaseStatus::PENDING => 'CAPTURAR / CONTINUAR',
            NotificationCaseStatus::REJECTED => 'CORREGIR',
            default => 'VER',
        };

        return ['label' => $label, 'url' => route('customer.notification-cases.show', $row->applicable_case_id)];
    }
}
