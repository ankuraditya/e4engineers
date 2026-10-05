<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use App\Enums\NotificationType;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['type' => NotificationType::class, 'channel' => NotificationChannel::class, 'is_enabled' => 'boolean', 'available_variables' => 'array'];
    }
}
