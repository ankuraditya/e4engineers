<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicketMessage extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_internal' => 'boolean'];
    }
}
