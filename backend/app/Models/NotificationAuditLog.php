<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationAuditLog extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['context' => 'array'];
    }
}
