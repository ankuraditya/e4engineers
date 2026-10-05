<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Coupons\StoreCouponRequest;
use App\Http\Requests\Api\V1\Coupons\UpdateCouponRequest;
use App\Http\Resources\Api\V1\CouponResource;
use App\Models\Coupon;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CouponController extends Controller
{
    use ApiResponse;

    private array $relations = ['books:id', 'categories:id', 'disciplines:id'];

    public function index(Request $r): JsonResponse
    {
        Gate::authorize('coupons.view');
        $q = Coupon::query()->when($r->search, fn ($q, $v) => $q->where(fn ($x) => $x->where('code', 'like', "%$v%")->orWhere('name', 'like', "%$v%")))->when($r->filled('active'), fn ($q) => $q->where('is_active', $r->boolean('active')))->when($r->discount_type, fn ($q, $v) => $q->where('discount_type', $v))->when($r->scope, fn ($q, $v) => $q->where('applies_to', $v))->when($r->validity === 'active', fn ($q) => $q->where(fn ($x) => $x->whereNull('starts_at')->orWhere('starts_at', '<=', now()))->where(fn ($x) => $x->whereNull('expires_at')->orWhere('expires_at', '>=', now())))->with($this->relations)->withCount('usages')->latest();
        $p = $q->paginate(min((int) $r->input('per_page', 20), 100));

        return $this->successResponse(CouponResource::collection($p), meta: ['current_page' => $p->currentPage(), 'last_page' => $p->lastPage(), 'total' => $p->total()]);
    }

    public function store(StoreCouponRequest $r): JsonResponse
    {
        Gate::authorize('coupons.create');
        $coupon = $this->persist(new Coupon, $r->validated(), $r->user()->id);

        return $this->successResponse(new CouponResource($coupon), 'Coupon created.', 201);
    }

    public function show(Coupon $coupon): JsonResponse
    {
        Gate::authorize('coupons.view');

        return $this->successResponse(new CouponResource($coupon->load($this->relations)->loadCount('usages')));
    }

    public function update(UpdateCouponRequest $r, Coupon $coupon): JsonResponse
    {
        Gate::authorize('coupons.update');

        return $this->successResponse(new CouponResource($this->persist($coupon, $r->validated(), $r->user()->id)), 'Coupon updated.');
    }

    public function status(Request $r, Coupon $coupon): JsonResponse
    {
        Gate::authorize('coupons.activate');
        $d = $r->validate(['is_active' => ['required', 'boolean']]);
        $coupon->update($d + ['updated_by' => $r->user()->id]);

        return $this->successResponse(new CouponResource($coupon), 'Coupon status updated.');
    }

    public function destroy(Coupon $coupon): JsonResponse
    {
        Gate::authorize('coupons.delete');
        $coupon->delete();

        return $this->successResponse(null, 'Coupon deleted.');
    }

    private function persist(Coupon $coupon, array $data, int $actor): Coupon
    {
        return DB::transaction(function () use ($coupon, $data, $actor) {
            $books = $data['book_ids'] ?? null;
            $categories = $data['category_ids'] ?? null;
            $disciplines = $data['discipline_ids'] ?? null;
            unset($data['book_ids'],$data['category_ids'],$data['discipline_ids']);
            $data[$coupon->exists ? 'updated_by' : 'created_by'] = $actor;
            $data['updated_by'] = $actor;
            $coupon->fill($data)->save();
            if ($books !== null) {
                $coupon->books()->sync($books);
            }if ($categories !== null) {
                $coupon->categories()->sync($categories);
            }if ($disciplines !== null) {
                $coupon->disciplines()->sync($disciplines);
            }

return $coupon->load($this->relations)->loadCount('usages');
        });
    }
}
