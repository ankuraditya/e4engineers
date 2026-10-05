<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseFaq extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
