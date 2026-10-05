<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\EntitlementSource;
use App\Http\Controllers\Controller;
use App\Models\DigitalEntitlement;
use App\Models\DigitalResource;
use App\Models\Publication;
use App\Models\User;
use App\Services\EntitlementService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class EntitlementController extends Controller
{
    use ApiResponse;

    public function __construct(private EntitlementService $service) {}

    public function index(Request $r): JsonResponse
    {
        Gate::authorize('entitlements.view');
        $p = DigitalEntitlement::query()->with(['user', 'entitleable', 'grantedBy'])->when($r->user_id, fn ($q, $v) => $q->where('user_id', $v))->when($r->content_type, fn ($q, $v) => $q->where('entitleable_type', $v))->when($r->status, fn ($q, $v) => $q->where('status', $v))->when($r->source, fn ($q, $v) => $q->where('source_type', $v))->latest()->paginate(min((int) $r->input('per_page', 20), 100));

        return $this->successResponse($p->items(), meta: ['current_page' => $p->currentPage(), 'last_page' => $p->lastPage(), 'total' => $p->total()]);
    }

    public function store(Request $r): JsonResponse
    {
        Gate::authorize('entitlements.grant');
        $d = $r->validate(['user_id' => 'required|exists:users,id', 'content_type' => ['required', Rule::in(['resource', 'publication'])], 'content_id' => 'required|integer', 'expires_at' => 'nullable|date|after:now', 'notes' => 'nullable|string|max:2000']);
        $class = $d['content_type'] === 'resource' ? DigitalResource::class : Publication::class;
        $content = $class::findOrFail($d['content_id']);
        $e = $this->service->grant(User::findOrFail($d['user_id']), $content, EntitlementSource::AdminGrant, null, isset($d['expires_at']) ? new \DateTimeImmutable($d['expires_at']) : null, $r->user(), $d['notes'] ?? null);

        return $this->successResponse($e->load(['user', 'entitleable']), 'Entitlement granted.', 201);
    }

    public function show(DigitalEntitlement $entitlement): JsonResponse
    {
        Gate::authorize('entitlements.view');

        return $this->successResponse($entitlement->load(['user', 'entitleable', 'grantedBy']));
    }

    public function revoke(Request $r, DigitalEntitlement $entitlement): JsonResponse
    {
        Gate::authorize('entitlements.revoke');
        $d = $r->validate(['reason' => 'nullable|string|max:2000']);

        return $this->successResponse($this->service->revoke($entitlement,$r->user(),$d['reason'] ?? null), 'Entitlement revoked.');
    }
}
