<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->when($request->is('api/v1/admin/*'), $this->id),
            'order_number' => $this->order_number, 'placed_at' => $this->placed_at,
            'status' => $this->status->value, 'payment_status' => $this->payment_status->value,
            'shipping_status' => $this->shipping_status->value, 'delivery_method' => $this->delivery_method, 'pickup_ready_at' => $this->pickup_ready_at, 'picked_up_at' => $this->picked_up_at, 'payment_method' => $this->payment_method,
            'currency' => $this->currency,
            'pricing' => ['subtotal' => $this->subtotal, 'discount' => $this->discount_total, 'shipping' => $this->shipping_total, 'cod_charge' => $this->cod_charge, 'tax' => $this->tax_total, 'store_credit' => $this->store_credit_total, 'total' => $this->grand_total],
            'coupon' => $this->coupon_snapshot, 'shipping' => $this->shipping_snapshot,
            'shipment' => $this->whenLoaded('shipment', fn () => $this->shipment ? ['status' => $this->shipment->status->value, 'courier_name' => $this->shipment->courier_name ?: $this->shipment->provider?->name, 'awb_number' => $this->shipment->awb_number, 'estimated_delivery_date' => $this->shipment->estimated_delivery_date, 'last_tracked_at' => $this->shipment->last_tracked_at] : null),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => ['book_id' => $item->book_id, 'sku' => $item->sku, 'title' => $item->title, 'slug' => $item->slug, 'authors' => $item->authors, 'cover_url' => $item->cover_url, 'quantity' => $item->quantity, 'unit_price' => $item->unit_price, 'discount' => $item->discount_total, 'line_total' => $item->line_total])),
            'shipping_address' => $this->whenLoaded('shippingAddress', fn () => $this->shippingAddress ? ['name' => $this->shippingAddress->name, 'email' => $this->shippingAddress->email, 'mobile' => $this->shippingAddress->mobile, 'address_line_1' => $this->shippingAddress->address_line1, 'address_line_2' => $this->shippingAddress->address_line2, 'landmark' => $this->shippingAddress->landmark, 'city' => $this->shippingAddress->city, 'state' => $this->shippingAddress->state, 'postal_code' => $this->shippingAddress->postal_code, 'country_code' => $this->shippingAddress->country] : null),
            'status_history' => $this->whenLoaded('histories', fn () => $this->histories->map(fn ($history) => ['type' => $history->status_type, 'from' => $history->from_status, 'to' => $history->to_status, 'note' => $history->note, 'at' => $history->created_at])),
        ];
    }
}
