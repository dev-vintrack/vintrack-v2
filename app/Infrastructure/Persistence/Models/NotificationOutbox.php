<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationOutbox extends Model
{
    protected $table = 'notification_outbox';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['payload' => 'array', 'available_at' => 'immutable_datetime'];
    }
}
