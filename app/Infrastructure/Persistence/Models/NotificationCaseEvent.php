<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class NotificationCaseEvent extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'occurred_at' => 'immutable_datetime', 'created_at' => 'immutable_datetime'];
    }

    public function save(array $options = []): bool
    {
        if ($this->exists) {
            throw new LogicException('Los eventos de expediente son append-only.');
        }

        return parent::save($options);
    }

    public function delete(): ?bool
    {
        throw new LogicException('Los eventos de expediente no pueden eliminarse.');
    }
}
