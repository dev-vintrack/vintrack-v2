<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationCaseDocumentScan extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['attempt' => 'integer', 'started_at' => 'immutable_datetime', 'finished_at' => 'immutable_datetime', 'created_at' => 'immutable_datetime'];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(NotificationCaseDocument::class, 'notification_case_document_id');
    }
}
