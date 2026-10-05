<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\GalleryAlbum;
use App\Models\GalleryImage;
use App\Models\Notice;
use App\Models\Video;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DiscoveryController extends Controller
{
    use ApiResponse;

    private function list(Request $r, string $model): JsonResponse
    {
        $q = $model::query();
        if ($r->filled('search')) {
            $q->where('title', 'like', '%'.$r->input('search').'%');
        }if ($r->filled('status')) {
            $q->where('status', $r->input('status'));
        }

        return $this->successResponse($q->latest()->paginate(min((int) $r->input('per_page', 20), 100)));
    }

    private function common(Request $r): array
    {
        return $r->validate(['title' => 'required|string|max:200', 'slug' => 'nullable|string|max:220', 'short_description' => 'nullable|string|max:1500', 'description' => 'nullable|string', 'engineering_discipline_id' => 'nullable|exists:engineering_disciplines,id', 'status' => 'required|in:draft,published,archived', 'is_featured' => 'nullable|boolean', 'sort_order' => 'nullable|integer|min:0']);
    }

    public function notices(Request $r): JsonResponse
    {
        return $this->list($r, Notice::class);
    }

    public function notice(Notice $notice): JsonResponse
    {
        return $this->successResponse($notice->load(['category', 'discipline', 'featuredMedia', 'attachmentMedia', 'seo']));
    }

    public function saveNotice(Request $r, ?Notice $notice = null): JsonResponse
    {
        $d = $this->common($r) + $r->validate(['content' => 'nullable|string', 'category_id' => 'nullable|exists:categories,id', 'featured_media_id' => 'nullable|exists:media,id', 'attachment_media_id' => 'nullable|exists:media,id', 'external_url' => 'nullable|url:http,https|max:1000', 'notice_date' => 'required|date', 'expires_at' => 'nullable|date', 'is_pinned' => 'nullable|boolean']);
        $d['slug'] = $notice?->slug ?? ($d['slug'] ?? Str::slug($d['title']));
        if ($d['status'] === 'published') {
            $d['published_at'] = $notice?->published_at ?? now();
        }$d[$notice ? 'updated_by' : 'created_by'] = $r->user()->id;
        $m = $notice ? tap($notice)->update($d) : Notice::create($d);

        return $this->successResponse($m->refresh(), 'Notice saved.', $notice ? 200 : 201);
    }

    public function deleteNotice(Notice $notice): JsonResponse
    {
        $notice->delete();

        return $this->successResponse(null, 'Notice deleted.');
    }

    public function galleries(Request $r): JsonResponse
    {
        return $this->list($r, GalleryAlbum::class);
    }

    public function gallery(GalleryAlbum $album): JsonResponse
    {
        return $this->successResponse($album->load(['images.media', 'cover']));
    }

    public function saveGallery(Request $r, ?GalleryAlbum $album = null): JsonResponse
    {
        $d = $this->common($r) + $r->validate(['cover_media_id' => 'nullable|exists:media,id', 'event_date' => 'nullable|date']);
        $d['slug'] = $album?->slug ?? ($d['slug'] ?? Str::slug($d['title']));
        if ($d['status'] === 'published') {
            $d['published_at'] = $album?->published_at ?? now();
        }$m = $album ? tap($album)->update($d) : GalleryAlbum::create($d + ['created_by' => $r->user()->id]);

        return $this->successResponse($m->refresh(), 'Album saved.', $album ? 200 : 201);
    }

    public function deleteGallery(GalleryAlbum $album): JsonResponse
    {
        $album->delete();

        return $this->successResponse(null, 'Album deleted.');
    }

    public function addImages(Request $r, GalleryAlbum $album): JsonResponse
    {
        $d = $r->validate(['images' => 'required|array|max:50', 'images.*.media_id' => 'required|distinct|exists:media,id', 'images.*.title' => 'nullable|string|max:200', 'images.*.caption' => 'nullable|string|max:2000', 'images.*.alt_text' => 'required|string|max:300', 'images.*.sort_order' => 'nullable|integer|min:0']);
        foreach ($d['images'] as $i => $image) {
            $album->images()->updateOrCreate(['media_id' => $image['media_id']], $image + ['sort_order' => $image['sort_order'] ?? $i]);
        }

        return $this->successResponse($album->images()->with('media')->get(), 'Images saved.');
    }

    public function reorder(Request $r, GalleryAlbum $album): JsonResponse
    {
        $ids = $r->validate(['image_ids' => 'required|array', 'image_ids.*' => 'integer|exists:gallery_images,id'])['image_ids'];
        DB::transaction(fn () => collect($ids)->each(fn ($id, $i) => $album->images()->whereKey($id)->update(['sort_order' => $i])));

        return $this->successResponse(null, 'Images reordered.');
    }

    public function deleteImage(GalleryImage $image): JsonResponse
    {
        $image->delete();

        return $this->successResponse(null, 'Image relation deleted.');
    }

    public function videos(Request $r): JsonResponse
    {
        return $this->list($r, Video::class);
    }

    public function saveVideo(Request $r, ?Video $video = null): JsonResponse
    {
        $d = $this->common($r) + $r->validate(['video_type' => 'required|in:youtube,vimeo,external', 'external_video_id' => 'nullable|string|max:100', 'external_url' => 'nullable|url:http,https|max:1000', 'thumbnail_media_id' => 'nullable|exists:media,id', 'category_id' => 'nullable|exists:categories,id', 'duration_seconds' => 'nullable|integer|min:0', 'published_date' => 'nullable|date']);
        if ($d['video_type'] === 'youtube' && ! preg_match('/^[A-Za-z0-9_-]{11}$/', $d['external_video_id'] ?? '')) {
            abort(422, 'Invalid YouTube video ID.');
        }if ($d['video_type'] === 'vimeo' && ! ctype_digit($d['external_video_id'] ?? '')) {
            abort(422, 'Invalid Vimeo video ID.');
        }$d['slug'] = $video?->slug ?? ($d['slug'] ?? Str::slug($d['title']));
        if ($d['status'] === 'published') {
            $d['published_at'] = $video?->published_at ?? now();
        }$m = $video ? tap($video)->update($d) : Video::create($d + ['created_by' => $r->user()->id]);

        return $this->successResponse($m->refresh(), 'Video saved.', $video ? 200 : 201);
    }

    public function deleteVideo(Video $video): JsonResponse
    {
        $video->delete();

        return $this->successResponse(null, 'Video deleted.');
    }
}
