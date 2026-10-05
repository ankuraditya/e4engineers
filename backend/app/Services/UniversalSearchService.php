<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Book;
use App\Models\Contributor;
use App\Models\Course;
use App\Models\DigitalResource;
use App\Models\JobOpening;
use App\Models\Notice;
use App\Models\Publication;
use App\Models\Video;
use App\Models\WebsiteSetting;
use App\Models\Workshop;
use Illuminate\Support\Collection;

class UniversalSearchService
{
    public function search(string $term, ?string $type = null, ?string $discipline = null, string $sort = 'relevance'): Collection
    {
        $definitions = [['article', Article::class, 'excerpt', '/articles/'], ['course', Course::class, 'short_description', '/courses/'], ['book', Book::class, 'short_description', '/books/'], ['publication', Publication::class, 'short_description', '/publications/'], ['resource', DigitalResource::class, 'short_description', '/resources/'], ['contributor', Contributor::class, 'short_bio', '/contributors/'], ['notice', Notice::class, 'short_description', '/notices/'], ['workshop', Workshop::class, 'short_description', '/workshops/'], ['career', JobOpening::class, 'summary', '/careers/'], ['video', Video::class, 'short_description', '/videos/']];

        return collect($definitions)->filter(fn ($d) => (! $type || $d[0] === $type) && ($d[0] !== 'publication' || WebsiteSetting::publicationsEnabled()))->flatMap(function ($d) use ($term, $discipline) {
            [$type,$model,$description,$prefix] = $d;
            $titleField = $type === 'contributor' ? 'name' : 'title';
            $q = $model::query();
            if ($type === 'contributor') {
                $q->where('is_active', true);
            } else {
                $q->where('status', 'published');
            }$q->whereNotNull('published_at')->where('published_at', '<=', now())->where(fn ($x) => $x->where($titleField, 'like', "%$term%")->orWhere($description, 'like', "%$term%"));
            if ($discipline && in_array($type, ['article', 'course', 'book', 'publication', 'resource', 'notice', 'video'], true)) {
                $q->whereHas('discipline', fn ($x) => $x->where('slug', $discipline));
            }

            return $q->limit(50)->get(['id', $titleField.' as title', 'slug', $description, 'published_at'])->map(fn ($x) => ['type' => $type, 'title' => $x->title, 'description' => $x->{$description}, 'url' => $prefix.$x->slug, 'published_at' => $x->published_at, 'score' => mb_strtolower($x->title) === mb_strtolower($term) ? 100 : (str_starts_with(mb_strtolower($x->title), mb_strtolower($term)) ? 70 : 40)]);
        })->sortByDesc($sort === 'latest' ? 'published_at' : 'score')->values();
    }
}
