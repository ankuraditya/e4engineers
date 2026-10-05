<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddCartItemRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private CartService $carts) {}

    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->carts->payload($this->carts->resolve($request))]);
    }

    public function store(AddCartItemRequest $request): JsonResponse
    {
        $cart = $this->carts->resolve($request);
        $this->carts->add($cart, $request->integer('book_id'), $request->integer('quantity'));

        return response()->json(['data' => $this->carts->payload($cart->refresh())], 201);
    }

    public function update(UpdateCartItemRequest $request, int $item): JsonResponse
    {
        $cart = $this->carts->resolve($request);
        $this->carts->update($cart, $item, $request->integer('quantity'));

        return response()->json(['data' => $this->carts->payload($cart->refresh())]);
    }

    public function destroy(Request $request, int $item): JsonResponse
    {
        $cart = $this->carts->resolve($request);
        $this->carts->remove($cart, $item);

        return response()->json(['data' => $this->carts->payload($cart->refresh())]);
    }

    public function clear(Request $request): JsonResponse
    {
        $cart = $this->carts->resolve($request);
        $this->carts->clear($cart);

        return response()->json(['data' => $this->carts->payload($cart->refresh())]);
    }

    public function merge(Request $request): JsonResponse
    {
        $warnings = $this->carts->merge($request);
        $cart = $this->carts->resolve($request);

        return response()->json(['data' => $this->carts->payload($cart->refresh(), $warnings)]);
    }
}
