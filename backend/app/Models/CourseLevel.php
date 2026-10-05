<?php

namespace App\Models;

use Database\Factories\CourseLevelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable(['name', 'slug', 'sort_order', 'is_active'])]
class CourseLevel extends MasterData
{
    /** @use HasFactory<CourseLevelFactory> */
    use HasFactory;
}
