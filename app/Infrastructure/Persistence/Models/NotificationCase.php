<?php

namespace App\Infrastructure\Persistence\Models;

use App\Domain\NotificationCases\Enums\NotificationCaseStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationCase extends Model
{
    protected $guarded = ['id', 'consultation_id', 'user_id', 'previous_case_id', 'case_number', 'vin', 'vin_key', 'status', 'notification_deadline_at', 'opened_at', 'auto_close_at', 'lock_version', 'creation_key'];

    protected function casts(): array
    {
        return [
            'status' => NotificationCaseStatus::class,
            'notification_deadline_at' => 'immutable_datetime:Y-m-d H:i:s',
            'opened_at' => 'immutable_datetime',
            'auto_close_at' => 'immutable_datetime',
            'recovered_at' => 'immutable_datetime',
            'submitted_at' => 'immutable_datetime',
            'last_submitted_at' => 'immutable_datetime',
            'review_started_at' => 'immutable_datetime',
            'rejected_at' => 'immutable_datetime',
            'validated_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
            'lock_version' => 'integer',
            'model_year' => 'integer',
        ];
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function previousCase(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_case_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(NotificationCaseDocument::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(NotificationCaseEvent::class)->orderBy('occurred_at')->orderBy('id');
    }
}
