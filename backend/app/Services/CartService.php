<?php

namespace App\Services;

use App\Enums\BookStatus;
use App\Enums\CartStatus;
use App\Exceptions\InsufficientStockException;
use App\Models\Book;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CartService
{
    public function __construct(private InventoryService $inventory, private CouponService $coupons) {}

    public function resolve(Request $request): Cart
    {
        if ($request->user()) {
            return Cart::firstOrCreate(['active_user_id' => $request->user()->id], ['user_id' => $request->user()->id, 'status' => CartStatus::Active, 'currency' => 'INR', 'last_activity_at' => now()]);
        }

        $token = (string) $request->header('X-Guest-Cart-Token');
        if (! preg_match('/^[A-Za-z0-9]{64}$/', $token)) {
            $token = Str::random(64);
        }

        return Cart::firstOrCreate(['guest_token' => $token], ['status' => CartStatus::Active, 'currency' => 'INR', 'last_activity_at' => now()]);
    }

    public function add(Cart $cart, int $bookId, int $quantity): void
    {
        DB::transaction(function () use ($cart, $bookId, $quantity): void {
            Cart::whereKey($cart->id)->lockForUpdate()->firstOrFail();
            $book = Book::query()->with('inventory')->lockForUpdate()->findOrFail($bookId);
            $this->assertPurchasable($book);
            $item = CartItem::where('cart_id', $cart->id)->where('book_id', $book->id)->lockForUpdate()->first();
            $target = ($item?->quantity ?? 0) + $quantity;
            $this->available($book, $target);
            if ($item) {
                $item->update(['quantity' => $target]);
            } else {
                CartItem::create(['cart_id' => $cart->id, 'book_id' => $book->id, 'quantity' => $quantity, 'unit_price_snapshot' => $book->selling_price]);
            }
            $cart->update(['last_activity_at' => now()]);
        });
    }

    public function update(Cart $cart, int $itemId, int $quantity): void
    {
        DB::transaction(function () use ($cart, $itemId, $quantity): void {
            Cart::whereKey($cart->id)->lockForUpdate()->firstOrFail();
            $item = CartItem::where('cart_id', $cart->id)->whereKey($itemId)->lockForUpdate()->firstOrFail();
            $book = Book::withTrashed()->with('inventory')->findOrFail($item->book_id);
            $this->assertPurchasable($book);
            $this->available($book, $quantity);
            $item->update(['quantity' => $quantity]);
            $cart->update(['last_activity_at' => now()]);
        });
    }

    public function remove(Cart $cart, int $itemId): void
    {
        $deleted = CartItem::where('cart_id', $cart->id)->whereKey($itemId)->delete();
        if (! $deleted) {
            abort(404);
        } $cart->update(['last_activity_at' => now()]);
    }

    public function clear(Cart $cart): void
    {
        $cart->items()->delete();
        $cart->update(['last_activity_at' => now()]);
    }

    public function merge(Request $request): array
    {
        $token = (string) $request->header('X-Guest-Cart-Token');
        $customer = $this->resolve($request);
        $warnings = [];
        if (! preg_match('/^[A-Za-z0-9]{64}$/', $token)) {
            return $warnings;
        }
        $guest = Cart::where('guest_token', $token)->where('status', CartStatus::Active)->whereNull('user_id')->first();
        if (! $guest || $guest->is($customer)) {
            return $warnings;
        }
        DB::transaction(function () use ($customer, $guest, &$warnings): void {
            Cart::whereIn('id', [$customer->id, $guest->id])->orderBy('id')->lockForUpdate()->get();
            foreach ($guest->items()->with('book.inventory')->get() as $source) {
                $available = $source->book && $source->book->status === BookStatus::Published ? ($source->book->inventory?->available_quantity ?? 0) : 0;
                $target = CartItem::where('cart_id', $customer->id)->where('book_id', $source->book_id)->lockForUpdate()->first();
                $requested = ($target?->quantity ?? 0) + $source->quantity;
                $accepted = min($requested, $available);
                if ($accepted < $requested) {
                    $warnings[] = ['code' => 'QUANTITY_CAPPED', 'book_id' => $source->book_id, 'requested' => $requested, 'accepted' => $accepted];
                }
                if ($accepted > 0) {
                    if ($target) {
                        $target->update(['quantity' => $accepted]);
                    } else {
                        CartItem::create(['cart_id' => $customer->id, 'book_id' => $source->book_id, 'quantity' => $accepted, 'unit_price_snapshot' => $source->unit_price_snapshot]);
                    }
                }
            }
            if (! $customer->coupon_id && $guest->coupon_id) {
                $customer->update(['coupon_id' => $guest->coupon_id, 'coupon_applied_at' => $guest->coupon_applied_at]);
            } elseif ($customer->coupon_id && $guest->coupon_id && $customer->coupon_id !== $guest->coupon_id) {
                $warnings[] = ['code' => 'GUEST_COUPON_REPLACED', 'message' => 'The coupon already on your account cart was retained.'];
            }
            $guest->update(['status' => CartStatus::Converted, 'guest_token' => null, 'active_user_id' => null]);
            $customer->update(['last_activity_at' => now()]);
        });

        return $warnings;
    }

    public function payload(Cart $cart, array $warnings = []): array
    {
        $cart->load(['items.book.inventory', 'items.book.authors', 'items.book.cover']);
        $items = [];
        $subtotal = 0;
        $issues = [];
        foreach ($cart->items as $item) {
            $book = $item->book;
            $available = $book?->inventory?->available_quantity ?? 0;
            $valid = $book && ! $book->trashed() && $book->status === BookStatus::Published;
            $price = $book ? (string) $book->selling_price : '0.00';
            $changed = $item->unit_price_snapshot !== null && $this->cents((string) $item->unit_price_snapshot) !== $this->cents($price);
            if (! $valid) {
                $issues[] = ['code' => 'UNAVAILABLE_BOOK', 'cart_item_id' => $item->id];
            } elseif ($available < $item->quantity) {
                $issues[] = ['code' => 'INSUFFICIENT_STOCK', 'cart_item_id' => $item->id];
            }
            if ($changed) {
                $issues[] = ['code' => 'PRICE_CHANGED', 'cart_item_id' => $item->id, 'old_price' => $item->unit_price_snapshot, 'current_price' => $price];
            }
            $line = $this->cents($price) * $item->quantity;
            $subtotal += $line;
            $items[] = ['cart_item_id' => $item->id, 'book_id' => $book?->id, 'slug' => $book?->slug, 'title' => $book?->title ?? 'Unavailable book', 'sku' => $book?->sku, 'cover' => $book?->cover ? ['url' => $book->cover->url, 'alt_text' => $book->cover->alt_text] : null, 'authors' => $book?->authors?->pluck('name')->values() ?? [], 'quantity' => $item->quantity, 'mrp' => $book?->mrp, 'current_price' => $price, 'line_subtotal' => $this->money($line), 'inventory_status' => ! $valid ? 'UNAVAILABLE' : ($book->inventory?->status ?? 'OUT_OF_STOCK'), 'quantity_available' => $available >= $item->quantity, 'price_changed' => $changed, 'old_price' => $changed ? $item->unit_price_snapshot : null];
        }
        $coupon = $this->coupons->revalidate($cart, $cart->user);
        if ($coupon['removed']) {
            $warnings[] = ['code' => $coupon['reason_code'], 'message' => $coupon['message']];
        }
        $discount = $this->cents($coupon['discount']);

        return ['items' => $items, 'summary' => ['item_count' => count($items), 'quantity_count' => array_sum(array_column($items, 'quantity')), 'subtotal' => $this->money($subtotal), 'eligible_coupon_subtotal' => $coupon['eligible_subtotal'], 'coupon_discount' => $coupon['discount'], 'discounted_subtotal' => $this->money(max(0, $subtotal - $discount)), 'payable_before_shipping' => $this->money(max(0, $subtotal - $discount)), 'currency' => $cart->currency], 'coupon' => $coupon['coupon'], 'issues' => $issues, 'warnings' => $warnings, 'checkout_allowed' => count($items) > 0 && $issues === [], 'meta' => ['guest_cart_token' => $cart->user_id ? null : $cart->guest_token]];
    }

    private function assertPurchasable(Book $book): void
    {
        if ($book->trashed() || $book->status !== BookStatus::Published) {
            throw ValidationException::withMessages(['book_id' => ['This book is not available for purchase.']]);
        }
    }

    private function available(Book $book, int $quantity): void
    {
        try {
            $this->inventory->ensureAvailable($book, $quantity);
        } catch (InsufficientStockException) {
            throw ValidationException::withMessages(['quantity' => ['The requested quantity is not available.']]);
        }
    }

    private function cents(string $value): int
    {
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }

    private function money(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
