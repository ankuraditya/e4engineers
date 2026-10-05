<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use App\Enums\NotificationType;
use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $hidden = ['context'];

    protected function casts(): array
    {
        return ['type' => NotificationType::class, 'channel' => NotificationChannel::class, 'context' => 'encrypted:array', 'queued_at' => 'datetime', 'sent_at' => 'datetime', 'failed_at' => 'datetime'];
    }

    public function template()
    {
        return $this->belongsTo(NotificationTemplate::class);
    }
}
