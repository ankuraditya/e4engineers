<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['email_enabled' => 'boolean', 'order_updates' => 'boolean', 'payment_updates' => 'boolean', 'shipping_updates' => 'boolean', 'learning_updates' => 'boolean', 'promotional_communications' => 'boolean'];
    }
}
