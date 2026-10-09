<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use App\Services\CartService;
use App\Services\Shipping\ShippingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    public function __construct(private CartService $carts, private ShippingService $shipping) {}

    public function quote(Request $request): JsonResponse
    {
        $data = $request->validate(['address_id' => ['nullable', 'integer'], 'postal_code' => ['nullable', 'regex:/^[1-9][0-9]{5}$/'], 'payment_mode' => ['nullable', 'in:prepaid,cod'], 'delivery_method' => ['nullable', 'in:courier,self_collect']]);
        if (($data['delivery_method'] ?? 'courier') === 'self_collect') {
            $result = $this->shipping->selfCollectQuote($this->carts->resolve($request), ($data['payment_mode'] ?? 'prepaid') === 'cod');

            return response()->json(['success' => true, 'message' => 'Self Collect available.', 'data' => $result]);
        }
        $postal = $this->postal($request, $data);
        $result = $this->shipping->quote($this->carts->resolve($request), $postal, ($data['payment_mode'] ?? 'prepaid') === 'cod');

        return response()->json(['success' => true, 'message' => 'Shipping options calculated.', 'data' => $result]);
    }

    public function serviceability(Request $request): JsonResponse
    {
        return $this->quote($request);
    }

    public function selfCollection(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->shipping->selfCollection()]);
    }

    private function postal(Request $request, array $data): string
    {
        if (! empty($data['address_id'])) {
            abort_unless($request->user(), 401);

            return CustomerAddress::where('user_id', $request->user()->id)->whereKey($data['address_id'])->firstOrFail()->postal_code;
        }if (! empty($data['postal_code'])) {
            return $data['postal_code'];
        }abort(422, 'A destination address or postal code is required.');
    }
}
