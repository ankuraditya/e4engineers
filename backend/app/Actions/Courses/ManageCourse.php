<?php

namespace App\Actions\Courses;

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Services\CourseCache;
use App\Services\HtmlSanitizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ManageCourse
{
    public function __construct(private CourseCache $cache, private HtmlSanitizer $sanitizer) {}

    public function create(array $data, int $user): Course
    {
        return $this->save(new Course, $data, $user, true);
    }

    public function update(Course $c, array $data, int $user): Course
    {
        return $this->save($c, $data, $user, false);
    }

    private function save(Course $c, array $data, int $user, bool $new): Course
    {
        return DB::transaction(function () use ($c, $data, $user, $new) {
            $out = $data['outcomes'] ?? null;
            $mods = $data['modules'] ?? null;
            $people = $data['contributors'] ?? null;
            $faqs = $data['faqs'] ?? null;
            unset($data['outcomes'],$data['modules'],$data['contributors'],$data['faqs']);
            if (isset($data['description'])) {
                $data['description'] = $this->sanitizer->clean($data['description']);
            }if ($new) {
                $data['slug'] ??= Str::slug($data['title']);
                $data['created_by'] = $user;
            }$data['updated_by'] = $user;
            if (($data['status'] ?? null) === CourseStatus::Published->value && empty($data['published_at'])) {
                $data['published_at'] = now();
            }$c->fill($data)->save();
            if ($out !== null) {
                $c->outcomes()->delete();
                foreach ($out as $i => $x) {
                    $c->outcomes()->create(['outcome' => $x['outcome'], 'sort_order' => $i]);
                }
            }if ($mods !== null) {
                $c->modules()->delete();
                foreach ($mods as $i => $m) {
                    $module = $c->modules()->create(['title' => $m['title'], 'description' => $this->sanitizer->clean($m['description'] ?? null), 'sort_order' => $i, 'is_active' => $m['is_active'] ?? true]);
                    foreach ($m['lessons'] ?? [] as $j => $l) {
                        $module->lessons()->create($l + ['sort_order' => $j, 'is_active' => $l['is_active'] ?? true]);
                    }
                }
            }if ($people !== null) {
                $pivot = [];
                foreach ($people as $i => $p) {
                    $pivot[$p['id']] = ['role' => $p['role'] ?? 'instructor', 'is_primary' => $p['is_primary'] ?? $i === 0, 'sort_order' => $i];
                }$c->contributors()->sync($pivot);
            }if ($faqs !== null) {
                $c->faqs()->delete();
                foreach ($faqs as $i => $f) {
                    $c->faqs()->create(['question' => $f['question'], 'answer' => $this->sanitizer->clean($f['answer']), 'sort_order' => $i, 'is_active' => $f['is_active'] ?? true]);
                }
            }$this->cache->flush();

            return $c->refresh()->load($this->relations());
        });
    }

    public function relations(): array
    {
        return ['discipline', 'level', 'featuredMedia', 'outcomes', 'modules.lessons', 'contributors.media', 'faqs', 'seo.ogMedia'];
    }
}
