<?php

namespace App\Services;

use App\Enums\InventoryMovementType;
use App\Enums\OrderStatus;
use App\Enums\ShippingStatus;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class OrderStatusService
{
    public function __construct(private readonly InventoryService $inventory) {}

    public function transition(Order $order, OrderStatus $target, int $actorId, ?string $note = null): Order
    {
        return DB::transaction(function () use ($order, $target, $actorId, $note): Order {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($order->status === $target) {
                return $order;
            }
            $allowed = [OrderStatus::PaymentPending->value => [OrderStatus::Cancelled], OrderStatus::Confirmed->value => [OrderStatus::Processing, OrderStatus::Cancelled], OrderStatus::Processing->value => [OrderStatus::Packed, OrderStatus::Cancelled], OrderStatus::Packed->value => [OrderStatus::Cancelled]];
            if (! in_array($target, $allowed[$order->status->value] ?? [], true)) {
                throw ValidationException::withMessages(['status' => ['This order status transition is not allowed.']]);
            }
            $from = $order->status;
            if ($target === OrderStatus::Cancelled && ! $order->inventory_restored_at) {
                $spent = DB::table('store_credit_entries')->where('order_id', $order->id)->where('reason', 'order_redemption')->first();
                if ($spent) {
                    DB::table('store_credit_entries')->where('id', $spent->id)->update(['amount_paise' => 0, 'reason' => 'cancelled_redemption', 'updated_at' => now()]);
                }
                $reward = DB::table('referral_rewards')->where('qualifying_order_id', $order->id)->whereNull('revoked_at')->first();
                if ($reward) {
                    DB::table('referral_rewards')->where('id', $reward->id)->update(['revoked_at' => now(), 'updated_at' => now()]);
                    DB::table('store_credit_entries')->where('referral_reward_id', $reward->id)->update(['amount_paise' => 0, 'reason' => 'revoked_referral', 'updated_at' => now()]);
                    DB::table('referral_wallet_entries')->where('referral_reward_id', $reward->id)->update(['amount_paise' => 0, 'reason' => 'revoked_referral', 'updated_at' => now()]);
                }
                foreach ($order->items()->with('book')->get() as $item) {
                    if ($item->book) {
                        if ($order->inventory_reserved_at && ! $order->inventory_finalized_at && ! $order->inventory_released_at) {
                            $this->inventory->releaseReservation($item->book, $item->quantity, $order->order_number);
                        } elseif (! $order->inventory_released_at) {
                            $this->inventory->increase($item->book, $item->quantity, InventoryMovementType::CancellationRestore, 'Admin order cancellation', $actorId, $note, 'order-cancellation', $order->order_number);
                        }
                    }
                }
                if ($order->inventory_reserved_at && ! $order->inventory_finalized_at) {
                    $order->inventory_released_at = now();
                }
                $order->inventory_restored_at = now();
                $order->cancelled_at = now();
                $order->shipping_status = ShippingStatus::Cancelled;
            }
            $order->status = $target;
            $order->save();
            $order->histories()->create(['status_type' => 'order', 'from_status' => $from->value, 'to_status' => $target->value, 'note' => $note, 'changed_by' => $actorId]);

            return $order->refresh();
        });
    }
}
