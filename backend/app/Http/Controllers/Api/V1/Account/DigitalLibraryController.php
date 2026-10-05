<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Controller;
use App\Models\DigitalDownloadLog;
use App\Models\DigitalEntitlement;
use App\Models\DigitalResource;
use App\Models\Publication;
use App\Services\DigitalAccessService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DigitalLibraryController extends Controller
{
    use ApiResponse;

    public function __construct(private DigitalAccessService $access) {}

    public function resources(Request $r): JsonResponse
    {
        $types = [DigitalResource::class, Publication::class];
        $q = DigitalEntitlement::query()->forUser($r->user())->valid()->with(['entitleable']);
        $q->when($r->type, fn ($x, $v) => $x->where('entitleable_type', $v))->when($r->search, fn ($x, $v) => $x->whereHasMorph('entitleable', $types, fn ($m) => $m->where('title', 'like', "%{$v}%")))->when($r->discipline, fn ($x, $v) => $x->whereHasMorph('entitleable', $types, fn ($m) => $m->whereHas('discipline', fn ($d) => $d->where('slug', $v))));
        $p = $q->latest('granted_at')->paginate(min((int) $r->input('per_page', 12), 100));
        $items = collect($p->items())->map(fn ($e) => $this->item($e, $r));

        return $this->successResponse($items, meta: ['current_page' => $p->currentPage(), 'last_page' => $p->lastPage(), 'total' => $p->total()]);
    }

    public function downloads(Request $r): JsonResponse
    {
        $entitlements = DigitalEntitlement::query()->forUser($r->user())->valid()->with('entitleable')->get()->filter(fn ($e) => $this->access->decision($r->user(), $e->entitleable)['allowed'])->map(function ($e) use ($r) {
            $item = $this->item($e, $r);
            $logs = DigitalDownloadLog::where('user_id', $r->user()->id)->whereMorphedTo('downloadable', $e->entitleable);
            $item['download_count'] = (clone $logs)->count();
            $item['last_downloaded_at'] = (clone $logs)->max('downloaded_at');

            return $item;
        })->values();

        return $this->successResponse($entitlements);
    }

    private function item(DigitalEntitlement $e, Request $r): array
    {
        $c = $e->entitleable;
        $image = $c instanceof DigitalResource ? $c->thumbnail : $c->featuredMedia;

        return ['entitlement_id' => $e->id, 'content_type' => $c->getMorphClass(), 'id' => $c->id, 'slug' => $c->slug, 'title' => $c->title, 'image' => $image?->url, 'discipline' => $c->discipline?->name, 'type' => $c->type?->name, 'access_granted_at' => $e->granted_at?->toISOString(), 'access_status' => $e->status->value, 'downloadable' => $this->access->decision($r->user(), $c)['allowed'], 'download_url' => url("/api/v1/{$c->getMorphClass()}s/{$c->slug}/download")];
    }
}
