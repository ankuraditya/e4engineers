<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class McqPracticeSet extends Model
{
    use SoftDeletes;

    protected $fillable = ['title', 'slug', 'subject', 'description', 'status', 'questions', 'created_by'];

    protected function casts(): array
    {
        return ['questions' => 'array'];
    }
}
