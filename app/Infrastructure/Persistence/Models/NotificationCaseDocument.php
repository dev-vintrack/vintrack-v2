<?php

namespace App\Infrastructure\Persistence\Models;

use App\Domain\NotificationCases\Enums\MalwareScanStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationCaseDocument extends Model
{
    protected $guarded = ['id', 'notification_case_id', 'storage_disk', 'storage_key', 'sha256', 'malware_scan_status', 'malware_scan_attempts', 'malware_scan_claim', 'malware_scan_claimed_at', 'malware_scan_next_attempt_at', 'malware_scanned_at', 'removed_at', 'removed_by_user_id'];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'malware_scan_status' => MalwareScanStatus::class,
            'malware_scan_attempts' => 'integer',
            'malware_scan_claimed_at' => 'immutable_datetime',
            'malware_scan_next_attempt_at' => 'immutable_datetime',
            'malware_scanned_at' => 'immutable_datetime',
            'removed_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    public function notificationCase(): BelongsTo
    {
        return $this->belongsTo(NotificationCase::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function remover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'removed_by_user_id');
    }
}
