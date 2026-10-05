<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Enums\ArticleStatus;
use App\Enums\BookStatus;
use App\Enums\CourseStatus;
use App\Enums\DigitalResourceStatus;
use App\Enums\PublicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Book;
use App\Models\Course;
use App\Models\DigitalResource;
use App\Models\Publication;
use App\Models\WebsiteSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $site = rtrim((string) config('seo.site_url'), '/');
        $urls = collect(['/', '/about/', '/articles/', '/courses/', '/contact/', '/engineering/'])
            ->map(fn (string $path): array => ['url' => $site.$path, 'lastmod' => null]);

        $groups = [
            'articles' => Article::query()->where('status', ArticleStatus::Published)->whereNotNull('published_at')->where('published_at', '<=', now()),
            'courses' => Course::query()->where('status', CourseStatus::Published)->where(fn (Builder $query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now())),
            'books' => Book::query()->where('status', BookStatus::Published)->where(fn (Builder $query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now())),
            'resources' => DigitalResource::query()->where('status', DigitalResourceStatus::Published),
        ];
        if (WebsiteSetting::publicationsEnabled()) {
            $groups['publications'] = Publication::query()->where('status', PublicationStatus::Published)
                ->where(fn (Builder $query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()));
        }

        foreach ($groups as $kind => $query) {
            $items = $query->with('seo')->get(['id', 'slug', 'published_at', 'updated_at']);
            if ($items->isNotEmpty() && ! in_array($kind, ['articles', 'courses'], true)) {
                $urls->push(['url' => "$site/$kind/", 'lastmod' => null]);
            }
            foreach ($items as $item) {
                if (str_starts_with($item->seo?->robots ?? '', 'noindex')) {
                    continue;
                }
                $canonical = "$site/$kind/".rawurlencode($item->slug).'/';
                if ($item->seo?->canonical_url && $item->seo->canonical_url !== $canonical) {
                    continue;
                }
                $urls->push(['url' => $canonical, 'lastmod' => $item->updated_at?->toDateString()]);
            }
        }

        $escape = fn (string $value): string => htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $entries = $urls->map(fn (array $entry): string => '  <url><loc>'.$escape($entry['url']).'</loc>'
            .($entry['lastmod'] ? '<lastmod>'.$escape($entry['lastmod']).'</lastmod>' : '').'</url>')->join("\n");
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n"
            .$entries."\n</urlset>\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
