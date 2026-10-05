<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkshopRegistration extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['registered_at' => 'datetime'];
    }
}
