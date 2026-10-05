<?php

namespace App\Services;

use App\Enums\CartStatus;
use App\Enums\InventoryMovementType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShippingStatus;
use App\Enums\UserStatus;
use App\Models\Cart;
use App\Models\CouponUsage;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\PaymentProvider;
use App\Models\PaymentSetting;
use App\Models\PaymentTransaction;
use App\Models\ShippingQuote;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

final class CheckoutService
{
    public function __construct(
        private readonly CartService $carts,
        private readonly InventoryService $inventory,
        private readonly CustomerAddressService $addresses,
    ) {}

    /** @return array{order: Order, access_token: ?string, account_created: bool, account_collision: bool, user: ?User} */
    public function place(Request $request, array $data): array
    {
        if ($existing = Order::where('idempotency_key', $data['idempotency_key'])->first()) {
            $this->assertIdempotentOwner($existing, $request, $data);

            return ['order' => $existing, 'access_token' => $existing->guest_access_token_encrypted, 'account_created' => false, 'account_collision' => false, 'user' => null, 'replayed' => true];
        }

        $cart = $this->carts->resolve($request);
        $accessToken = Str::random(64);

        return DB::transaction(function () use ($request, $data, $cart, $accessToken): array {
            $lockedCart = Cart::whereKey($cart->id)->lockForUpdate()->firstOrFail();
            if ($lockedCart->status !== CartStatus::Active) {
                throw ValidationException::withMessages(['cart' => ['This cart has already been checked out.']]);
            }

            if ($existing = Order::where('idempotency_key', $data['idempotency_key'])->lockForUpdate()->first()) {
                $this->assertIdempotentOwner($existing, $request, $data);

                return ['order' => $existing, 'access_token' => $existing->guest_access_token_encrypted, 'account_created' => false, 'account_collision' => false, 'user' => null, 'replayed' => true];
            }

            [$customer, $accountCreated, $accountCollision, $contact] = $this->resolveCustomer($request, $data);
            $online = $data['payment_method'] !== 'cod';
            $settings = PaymentSetting::current();
            if ((! $online && ! $settings->cod_enabled) || ($online && $data['payment_method'] !== 'scanpay' && ! $settings->online_payments_enabled)) {
                throw ValidationException::withMessages(['payment_method' => ['This payment method is currently unavailable.']]);
            }
            if (! $online && PaymentProvider::where('code', 'COD')->where('is_enabled', false)->exists()) {
                throw ValidationException::withMessages(['payment_method' => ['Cash on delivery is currently unavailable.']]);
            }
            if ($data['payment_method'] === 'scanpay') {
                $provider = PaymentProvider::where('code', 'SCANPAY')->where('type', 'manual')->where('is_enabled', true)->first();
                if (! $provider || blank($provider->configuration['qr_path'] ?? null)) {
                    throw ValidationException::withMessages(['payment_method' => ['Scan & Pay is currently unavailable.']]);
                }
            } elseif ($online) {
                $provider = PaymentProvider::where('code', strtoupper($data['payment_method']))->where('type', 'online')->where('is_enabled', true)->first();
                if (! $provider || $provider->connection_status !== 'connected') {
                    throw ValidationException::withMessages(['payment_method' => ['The selected payment gateway is unavailable.']]);
                }
            }
            $address = $this->resolveAddress($request, $customer, $data, $accountCreated);
            $cartData = $this->carts->payload($lockedCart->refresh());
            if (! $cartData['checkout_allowed']) {
                throw ValidationException::withMessages(['cart' => ['Your cart changed. Review it before placing the order.']]);
            }

            $quote = ShippingQuote::whereKey($data['shipping_quote_id'])->where('cart_id', $lockedCart->id)->lockForUpdate()->first();
            $expectedHash = hash('sha256', $lockedCart->id.'|'.$lockedCart->updated_at.'|'.$address['postal_code'].'|'.($online ? 0 : 1));
            if (! $quote || $quote->expires_at->isPast() || ! hash_equals($quote->request_hash, $expectedHash) || (! $online && ! $quote->cod_available)) {
                throw ValidationException::withMessages(['shipping_quote_id' => ['Shipping changed or expired. Calculate shipping again.']]);
            }
            if ($customer && ! $lockedCart->user_id) {
                $lockedCart->user_id = $customer->id;
                $lockedCart->saveQuietly();
            }

            $shipping = $this->cents($quote->charge);
            $codCharge = $online ? 0 : $this->cents((string) $settings->cod_charge ?: $quote->cod_charge);
            $subtotal = $this->cents($cartData['summary']['subtotal']);
            $discount = $this->cents($cartData['summary']['coupon_discount']);
            $grandTotal = $subtotal - $discount + $shipping + $codCharge;
            $order = Order::create([
                'order_number' => $this->orderNumber(), 'user_id' => $customer?->id, 'cart_id' => $lockedCart->id,
                'guest_email' => $request->user() ? null : $contact['email'], 'guest_mobile' => $request->user() ? null : $contact['mobile'],
                'status' => $online ? OrderStatus::PaymentPending : OrderStatus::Confirmed, 'payment_status' => $online ? PaymentStatus::Pending : PaymentStatus::CodPending,
                'shipping_status' => ShippingStatus::NotCreated, 'payment_method' => $data['payment_method'], 'currency' => $lockedCart->currency,
                'subtotal' => $this->money($subtotal), 'discount_total' => $this->money($discount),
                'shipping_total' => $this->money($shipping), 'cod_charge' => $this->money($codCharge), 'tax_total' => '0.00',
                'grand_total' => $this->money($grandTotal), 'coupon_id' => $lockedCart->coupon_id,
                'coupon_snapshot' => $cartData['coupon'], 'shipping_quote_id' => $quote->id,
                'shipping_snapshot' => ['provider' => $quote->provider_code, 'courier_code' => $quote->courier_code, 'courier_name' => $quote->courier_name, 'estimated_delivery' => $quote->estimated_delivery, 'quoted_at' => $quote->quoted_at],
                'idempotency_key' => $data['idempotency_key'], 'guest_access_token_hash' => hash('sha256', $accessToken), 'guest_access_token_encrypted' => $accessToken, 'placed_at' => now(),
            ]);

            $remainingDiscount = $discount;
            $items = $lockedCart->items()->with(['book.inventory', 'book.authors', 'book.cover'])->orderBy('id')->get();
            foreach ($items as $index => $item) {
                $book = $item->book;
                $unit = $this->cents((string) $book->selling_price);
                $lineSubtotal = $unit * $item->quantity;
                $lineDiscount = $index === $items->count() - 1 ? $remainingDiscount : min($remainingDiscount, intdiv($discount * $lineSubtotal, max(1, $subtotal)));
                $remainingDiscount -= $lineDiscount;
                $order->items()->create([
                    'book_id' => $book->id, 'sku' => $book->sku, 'title' => $book->title, 'slug' => $book->slug,
                    'authors' => $book->authors->pluck('name')->values(), 'cover_url' => $book->cover?->url,
                    'quantity' => $item->quantity, 'unit_price' => $this->money($unit), 'discount_total' => $this->money($lineDiscount),
                    'line_total' => $this->money($lineSubtotal - $lineDiscount),
                    'product_snapshot' => ['book_id' => $book->id, 'sku' => $book->sku, 'title' => $book->title, 'slug' => $book->slug, 'mrp' => $book->mrp, 'selling_price' => $book->selling_price],
                ]);
                if ($online) {
                    $this->inventory->reserve($book, $item->quantity, 'order', $order->order_number);
                } else {
                    $this->inventory->decrease($book, $item->quantity, InventoryMovementType::Sale, 'Confirmed COD order', $customer?->id, null, 'order', $order->order_number);
                }
            }

            $order->shippingAddress()->create(['type' => 'shipping', 'name' => $address['full_name'], 'email' => $contact['email'], 'mobile' => $address['mobile'], 'address_line1' => $address['address_line_1'], 'address_line2' => $address['address_line_2'] ?? null, 'landmark' => $address['landmark'] ?? null, 'city' => $address['city'], 'state' => $address['state'], 'postal_code' => $address['postal_code'], 'country' => $address['country_code'] ?? 'IN']);
            if ($online) {
                $order->update(['inventory_reserved_at' => now()]);
            }
            if ($data['payment_method'] === 'scanpay') {
                PaymentAttempt::create([
                    'id' => (string) Str::uuid(), 'order_id' => $order->id, 'payment_provider_id' => $provider->id,
                    'environment' => 'offline', 'status' => 'pending', 'amount' => $order->grand_total,
                    'currency' => $order->currency, 'idempotency_key' => $data['idempotency_key'].':scanpay',
                    'expires_at' => now()->addDays(7),
                ]);
            } elseif (! $online && $codProvider = PaymentProvider::where('code', 'COD')->first()) {
                $attempt = PaymentAttempt::create([
                    'id' => (string) Str::uuid(), 'order_id' => $order->id, 'payment_provider_id' => $codProvider->id,
                    'environment' => 'offline', 'status' => 'cod_pending', 'amount' => $order->grand_total,
                    'currency' => $order->currency, 'idempotency_key' => $data['idempotency_key'].':cod',
                    'expires_at' => now()->addYears(5),
                ]);
                PaymentTransaction::create([
                    'payment_attempt_id' => $attempt->id, 'order_id' => $order->id, 'provider_code' => 'COD',
                    'type' => 'payment', 'status' => 'pending', 'amount' => $order->grand_total,
                    'currency' => $order->currency, 'payload' => ['collection' => 'on_delivery'],
                ]);
            }
            $order->histories()->create(['status_type' => 'order', 'to_status' => $order->status->value, 'note' => $online ? 'Awaiting online payment' : 'Order placed']);

            if ($lockedCart->coupon_id && $discount > 0) {
                CouponUsage::create(['coupon_id' => $lockedCart->coupon_id, 'user_id' => $customer?->id, 'order_id' => $order->id, 'reference_type' => 'order', 'reference_id' => $order->order_number, 'discount_amount' => $this->money($discount), 'used_at' => now()]);
            }

            $lockedCart->update(['status' => CartStatus::Converted, 'active_user_id' => null, 'guest_token' => null, 'coupon_id' => null, 'coupon_applied_at' => null, 'last_activity_at' => now()]);

            if (! $online) {
                DB::afterCommit(fn () => app(InvoiceService::class)->issueForOrder($order));
            }

            return ['order' => $order->load(['items', 'shippingAddress', 'histories']), 'access_token' => $accessToken, 'account_created' => $accountCreated, 'account_collision' => $accountCollision, 'user' => $customer, 'replayed' => false];
        }, 3);
    }

    private function resolveCustomer(Request $request, array $data): array
    {
        if ($request->user()) {
            return [$request->user(), false, false, ['name' => $request->user()->name, 'email' => $request->user()->email, 'mobile' => $request->user()->mobile]];
        }

        $contact = $data['contact'];
        $contact['email'] = Str::lower(trim($contact['email']));
        $contact['mobile'] = preg_replace('/\D+/', '', $contact['mobile']);
        $existing = User::whereRaw('LOWER(email) = ?', [$contact['email']])->orWhere('mobile', $contact['mobile'])->lockForUpdate()->first();
        if ($existing) {
            return [null, false, true, $contact];
        }

        $user = User::create(['name' => trim($contact['name']), 'email' => $contact['email'], 'mobile' => $contact['mobile'], 'password' => Hash::make(Str::random(64)), 'status' => UserStatus::Active, 'password_setup_required' => true]);
        if (Role::where('name', 'customer')->where('guard_name', 'web')->exists()) {
            $user->assignRole('customer');
        }

        return [$user, true, false, $contact];
    }

    private function resolveAddress(Request $request, ?User $customer, array $data, bool $accountCreated): array
    {
        if (! empty($data['address_id'])) {
            if (! $request->user()) {
                abort(404);
            }
            $address = CustomerAddress::where('user_id', $request->user()->id)->whereKey($data['address_id'])->firstOrFail();

            return $address->only(['type', 'full_name', 'mobile', 'address_line_1', 'address_line_2', 'landmark', 'city', 'state', 'postal_code', 'country_code']);
        }

        $address = $data['shipping_address'];
        $address['type'] ??= 'home';
        $address['country_code'] ??= 'IN';
        if ($customer && ($accountCreated || $request->user())) {
            $this->addresses->create($customer, $address + ['is_default' => $accountCreated]);
        }

        return $address;
    }

    private function assertIdempotentOwner(Order $order, Request $request, array $data): void
    {
        $allowed = $request->user()
            ? $order->user_id === $request->user()->id
            : isset($data['contact']['email'], $data['contact']['mobile'])
                && $order->guest_email === Str::lower(trim($data['contact']['email']))
                && $order->guest_mobile === preg_replace('/\D+/', '', $data['contact']['mobile']);
        abort_unless($allowed, 409, 'The idempotency key belongs to another checkout.');
    }

    private function orderNumber(): string
    {
        return 'E4E-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
    }

    private function cents(string $value): int
    {
        [$w, $f] = array_pad(explode('.', $value, 2), 2, '');

        return ((int) $w * 100) + (int) str_pad(substr($f, 0, 2), 2, '0');
    }

    private function money(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
