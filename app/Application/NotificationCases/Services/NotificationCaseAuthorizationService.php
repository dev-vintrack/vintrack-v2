<?php

namespace App\Application\NotificationCases\Services;

use App\Infrastructure\Persistence\Models\NotificationCase;
use App\Models\User;

final class NotificationCaseAuthorizationService
{
    public function canView(User $actor, NotificationCase $case): bool
    {
        return $case->user_id === $actor->id || $this->isAdministrative($actor);
    }

    public function canEditOwn(User $actor, NotificationCase $case): bool
    {
        return $case->user_id === $actor->id && $case->status->isEditableByOwner();
    }

    public function canAssignVinOnce(User $actor, NotificationCase $case): bool
    {
        return $this->canEditOwn($actor, $case) && $case->vin === null;
    }

    public function canSubmit(User $actor, NotificationCase $case): bool
    {
        return $this->canEditOwn($actor, $case);
    }

    public function canReview(User $actor): bool
    {
        return in_array($actor->rol, ['analista', 'admin'], true);
    }

    public function canValidate(User $actor): bool
    {
        return in_array($actor->rol, ['analista', 'admin'], true);
    }

    public function canListDocuments(User $actor, NotificationCase $case): bool
    {
        return $this->canView($actor, $case);
    }

    public function canManageDocuments(User $actor, NotificationCase $case): bool
    {
        if ($case->user_id === $actor->id) {
            return $case->status->isEditableByOwner();
        }

        return $this->isAdministrative($actor);
    }

    private function isAdministrative(User $actor): bool
    {
        return in_array($actor->rol, ['analista', 'admin'], true);
    }
}
