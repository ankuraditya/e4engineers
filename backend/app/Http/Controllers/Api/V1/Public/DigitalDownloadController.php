<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\DigitalDownloadLog;
use App\Models\DigitalResource;
use App\Models\Publication;
use App\Models\WebsiteSetting;
use App\Services\DigitalAccessService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DigitalDownloadController extends Controller
{
    public function __construct(private DigitalAccessService $access) {}

    public function resource(Request $r, string $slug): StreamedResponse
    {
        return $this->deliver($r, DigitalResource::where('slug', $slug)->firstOrFail());
    }

    public function publication(Request $r, string $slug): StreamedResponse
    {
        abort_unless(WebsiteSetting::publicationsEnabled(), 404);

        return $this->deliver($r, Publication::where('slug', $slug)->firstOrFail());
    }

    private function deliver(Request $r, Model $content): StreamedResponse
    {
        $decision = $this->access->decision($r->user(), $content);
        if ($decision['reason'] === 'authentication_required') {
            abort(401, 'Authentication required.');
        }if ($decision['reason'] === 'entitlement_required') {
            abort(403, 'You do not have access to this resource.');
        }if (! $decision['allowed']) {
            abort(404, 'The requested file is not available.');
        }$media = $content->fileMedia;
        if (! $media || $media->disk !== 'private' || ! Storage::disk('private')->exists($media->path)) {
            Log::warning('Protected digital file is unavailable.', ['type' => $content->getMorphClass(), 'id' => $content->id]);
            abort(404, 'The requested file is not available.');
        }$log = DigitalDownloadLog::create(['user_id' => $r->user()?->id, 'downloadable_type' => $content->getMorphClass(), 'downloadable_id' => $content->id, 'entitlement_id' => $decision['entitlement']?->id, 'ip_address' => $r->ip(), 'user_agent' => mb_substr((string) $r->userAgent(), 0, 500), 'downloaded_at' => now()]);
        $name = str($content->slug)->finish('.'.strtolower($media->extension))->replaceMatches('/[^a-zA-Z0-9._-]/', '-')->toString();

        return Storage::disk('private')->download($media->path, $name, ['Content-Type' => $media->mime_type, 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }
}
