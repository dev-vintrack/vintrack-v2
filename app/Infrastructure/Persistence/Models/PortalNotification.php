<?php

namespace App\Infrastructure\Persistence\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PortalNotification extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'outbox_id', 'recipient_user_id', 'case_id', 'type', 'title', 'body', 'action_path', 'read_at', 'created_at',
    ];

    protected function casts(): array
    {
        return ['read_at' => 'immutable_datetime', 'created_at' => 'immutable_datetime'];
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }
}
