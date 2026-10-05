<?php

namespace App\Models;

use App\Enums\CourseMode;
use App\Enums\CourseStatus;
use App\Enums\EnrollmentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['status' => CourseStatus::class, 'mode' => CourseMode::class, 'enrollment_type' => EnrollmentType::class, 'is_featured' => 'boolean', 'fee' => 'decimal:2', 'start_date' => 'date', 'end_date' => 'date', 'published_at' => 'datetime'];
    }

    public function discipline()
    {
        return $this->belongsTo(EngineeringDiscipline::class, 'engineering_discipline_id');
    }

    public function level()
    {
        return $this->belongsTo(CourseLevel::class, 'course_level_id');
    }

    public function featuredMedia()
    {
        return $this->belongsTo(Media::class, 'featured_media_id');
    }

    public function outcomes()
    {
        return $this->hasMany(CourseLearningOutcome::class)->orderBy('sort_order');
    }

    public function modules()
    {
        return $this->hasMany(CourseModule::class)->orderBy('sort_order');
    }

    public function contributors()
    {
        return $this->belongsToMany(Contributor::class, 'course_contributor')->withPivot(['role', 'is_primary', 'sort_order'])->orderByPivot('sort_order');
    }

    public function faqs()
    {
        return $this->hasMany(CourseFaq::class)->orderBy('sort_order');
    }

    public function seo()
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }
}
