<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CareerApplication extends Model
{
    protected $guarded = [];

    protected $hidden = ['resume_path'];

    protected function casts(): array
    {
        return ['applied_at' => 'datetime'];
    }
}
