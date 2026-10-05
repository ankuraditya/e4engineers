<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Cms\MediaUpdateRequest;
use App\Http\Requests\Api\V1\Cms\MediaUploadRequest;
use App\Http\Resources\Api\V1\MediaResource;
use App\Models\Article;
use App\Models\Banner;
use App\Models\Contributor;
use App\Models\DigitalResource;
use App\Models\Media;
use App\Models\PageSection;
use App\Models\Publication;
use App\Models\SeoMeta;
use App\Models\WebsiteSetting;
use App\Services\CmsCache;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    use ApiResponse;

    public function __construct(private CmsCache $cache) {}

    public function index(Request $r): JsonResponse
    {
        Gate::authorize('media.view');
        $items = Media::query()->when($r->search, fn ($q, $v) => $q->where(fn ($x) => $x->where('original_name', 'like', "%{$v}%")->orWhere('title', 'like', "%{$v}%")))->when($r->type, fn ($q, $v) => $q->where('mime_type', 'like', $v.'/%'))->latest()->paginate(min((int) $r->input('per_page', 20), 100));

        return $this->successResponse(MediaResource::collection($items), meta: ['current_page' => $items->currentPage(), 'total' => $items->total()]);
    }

    public function store(MediaUploadRequest $r): JsonResponse
    {
        Gate::authorize('media.upload');
        $file = $r->file('file');
        $disk = $r->validated('storage', 'public') === 'private' ? 'private' : config('cms.media_disk');
        if ($disk === 'private') {
            Gate::authorize('resources.file.manage');
        }
        $path = $file->store(($disk === 'private' ? 'files/' : 'cms/').now()->format('Y/m'), $disk);
        abort_unless($path, 500, 'Upload failed.');
        $dimensions = str_starts_with((string) $file->getMimeType(), 'image/') ? @getimagesize($file->getRealPath()) : false;
        $media = Media::create(['disk' => $disk, 'path' => $path, 'filename' => basename($path), 'original_name' => basename($file->getClientOriginalName()), 'mime_type' => $file->getMimeType(), 'extension' => strtolower($file->guessExtension() ?: $file->extension()), 'size' => $file->getSize(), 'width' => $dimensions[0] ?? null, 'height' => $dimensions[1] ?? null, 'alt_text' => $r->validated('alt_text'), 'title' => $r->validated('title'), 'caption' => $r->validated('caption'), 'uploaded_by' => $r->user()->id]);

        return $this->successResponse(new MediaResource($media), 'Media uploaded.', 201);
    }

    public function show(Media $media): JsonResponse
    {
        Gate::authorize('media.view');

        return $this->successResponse(new MediaResource($media));
    }

    public function update(MediaUpdateRequest $r, Media $media): JsonResponse
    {
        Gate::authorize('media.update');
        $media->update($r->validated());

        return $this->successResponse(new MediaResource($media->refresh()));
    }

    public function destroy(Media $media): JsonResponse
    {
        Gate::authorize('media.delete');
        $referenced = PageSection::where('media_id', $media->id)->exists() || SeoMeta::where('og_media_id', $media->id)->exists() || Banner::where('desktop_media_id', $media->id)->orWhere('mobile_media_id', $media->id)->exists() || Contributor::where('media_id', $media->id)->exists() || Article::where('featured_media_id', $media->id)->exists() || Publication::where('featured_media_id', $media->id)->orWhere('preview_media_id', $media->id)->orWhere('file_media_id', $media->id)->exists() || DigitalResource::where('thumbnail_media_id', $media->id)->orWhere('preview_media_id', $media->id)->orWhere('file_media_id', $media->id)->exists() || WebsiteSetting::whereIn('type', ['media'])->where('value', (string) $media->id)->exists();
        abort_if($referenced, 409, 'Media is still referenced and cannot be deleted.');
        Storage::disk($media->disk)->delete($media->path);
        $media->delete();
        $this->cache->forget('settings', 'banners:all');

        return $this->successResponse(null, 'Media deleted.');
    }
}
