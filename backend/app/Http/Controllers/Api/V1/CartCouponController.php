<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartCouponController extends Controller
{
    public function __construct(private CartService $carts, private CouponService $coupons) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:64']]);
        $cart = $this->carts->resolve($request);
        $this->coupons->apply($cart, $data['code'], $request->user());

        return response()->json(['success' => true, 'message' => 'Coupon applied.', 'data' => $this->carts->payload($cart->refresh())]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $cart = $this->carts->resolve($request);
        $this->coupons->remove($cart);

        return response()->json(['success' => true, 'message' => 'Coupon removed.', 'data' => $this->carts->payload($cart->refresh())]);
    }
}
