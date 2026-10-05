<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

class CourseResource extends ApiResource
{
    public function toArray(Request $r): array
    {
        $primary = $this->contributors->first(fn ($x) => (bool) $x->pivot->is_primary) ?? $this->contributors->first();
        $detail = $r->route('slug') !== null;

        return ['id' => $this->id, 'title' => $this->title, 'slug' => $this->slug, 'short_description' => $this->short_description, 'description' => $this->when($detail, $this->description), 'discipline' => $this->discipline ? ['name' => $this->discipline->name, 'slug' => $this->discipline->slug] : null, 'level' => $this->level ? ['name' => $this->level->name, 'slug' => $this->level->slug] : null, 'mode' => $this->mode->value, 'duration' => $this->duration_value ? ['value' => $this->duration_value, 'unit' => $this->duration_unit, 'formatted' => $this->duration_value.' '.$this->duration_unit] : null, 'eligibility' => $this->when($detail, $this->eligibility), 'featured_image' => $this->featuredMedia ? ['url' => $this->featuredMedia->url, 'alt_text' => $this->featuredMedia->alt_text, 'caption' => $this->featuredMedia->caption] : null, 'fee' => $this->fee, 'currency' => $this->currency, 'start_date' => $this->start_date, 'enrollment' => ['type' => $this->enrollment_type->value, 'url' => $this->enrollment_url], 'primary_instructor' => $primary ? ['name' => $primary->name, 'slug' => $primary->slug] : null, 'contributors' => $this->when($detail, $this->contributors->map(fn ($x) => ['name' => $x->name, 'slug' => $x->slug, 'role' => $x->pivot->role, 'is_primary' => (bool) $x->pivot->is_primary])), 'learning_outcomes' => $this->when($detail, $this->outcomes->pluck('outcome')), 'curriculum' => $this->when($detail, $this->modules->where('is_active', true)->map(fn ($m) => ['title' => $m->title, 'description' => $m->description, 'lessons' => $m->lessons->where('is_active', true)->map(fn ($l) => ['title' => $l->title, 'description' => $l->description, 'lesson_type' => $l->lesson_type, 'duration_minutes' => $l->duration_minutes])->values()])->values()), 'faqs' => $this->when($detail, $this->faqs->where('is_active', true)->map(fn ($f) => ['question' => $f->question, 'answer' => $f->answer])->values()), 'is_featured' => $this->is_featured, 'published_at' => $this->published_at, 'seo' => $this->whenLoaded('seo', fn () => new SeoResource($this->seo))];
    }
}
