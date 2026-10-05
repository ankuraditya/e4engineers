<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Enums\PageStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BannerResource;
use App\Http\Resources\Api\V1\PageResource;
use App\Http\Resources\Api\V1\SocialLinkResource;
use App\Models\Banner;
use App\Models\Media;
use App\Models\Page;
use App\Models\SocialLink;
use App\Models\WebsiteSetting;
use App\Services\CmsCache;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CmsController extends Controller
{
    use ApiResponse;

    public function __construct(private CmsCache $cache) {}

    public function page(string $slug): JsonResponse
    {
        $page = $this->cache->remember("page:{$slug}", function () use ($slug) {
            $page = Page::query()->where('slug', $slug)->where('status', PageStatus::Published->value)->with(['sections' => fn ($q) => $q->where('is_active', true)->with('media'), 'seo.ogMedia'])->first();

            return $page ? (new PageResource($page))->resolve() : null;
        });
        abort_unless($page, 404);

        return $this->successResponse($page);
    }

    public function settings(): JsonResponse
    {
        $items = WebsiteSetting::query()->where('is_public', true)->get();
        $media = Media::query()->whereIn('id', $items->where('type', 'media')->pluck('value')->filter())->get()->keyBy('id');
        $data = $items->mapWithKeys(fn ($item) => [$item->key => $item->type === 'media' ? $media->get((int) $item->value)?->url : $item->value])->all();

        return $this->successResponse($data);
    }

    public function socialLinks(): JsonResponse
    {
        $items = $this->cache->remember('social-links', fn () => SocialLinkResource::collection(SocialLink::query()->where('is_active', true)->orderBy('sort_order')->get())->resolve());

        return $this->successResponse($items);
    }

    public function banners(Request $request): JsonResponse
    {
        $placement = $request->validate(['placement' => ['nullable', 'string', 'max:40']])['placement'] ?? null;
        $key = 'banners:'.($placement ?? 'all');
        $items = $this->cache->remember($key, fn () => BannerResource::collection(Banner::query()->where('is_active', true)->when($placement, fn ($q) => $q->where('placement', $placement))->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', Carbon::now()))->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', Carbon::now()))->with(['desktopMedia', 'mobileMedia'])->orderBy('sort_order')->get())->resolve($request));

        return $this->successResponse($items);
    }
}
