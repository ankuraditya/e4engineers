<?php

namespace App\Services\Shipping;

use App\Exceptions\ShippingProviderException;
use App\Models\Cart;
use App\Models\ShippingPickupLocation;
use App\Models\ShippingQuote;
use App\Models\ShippingSetting;
use App\Services\CartService;
use Illuminate\Support\Str;

class ShippingService
{
    public function __construct(private CartService $carts, private PackageCalculator $packages, private ShippingProviderManager $providers) {}

    public function quote(Cart $cart, string $postalCode, bool $cod = false): array
    {
        $settings = ShippingSetting::current();
        if (! $settings->shipping_enabled) {
            throw new ShippingProviderException('SHIPPING_DISABLED', 'Shipping is temporarily unavailable.');
        }$cartData = $this->carts->payload($cart);
        if (! $cartData['checkout_allowed']) {
            throw new ShippingProviderException('CART_NOT_READY', 'Resolve cart issues before calculating shipping.');
        }$discounted = $cartData['summary']['discounted_subtotal'];
        if ($settings->free_shipping_enabled && $settings->free_shipping_threshold !== null && $this->cents($discounted) >= $this->cents($settings->free_shipping_threshold)) {
            return $this->persist($cart, [['provider' => 'E4ENGINEERS', 'courier_code' => 'FREE', 'courier_name' => 'Free shipping', 'charge' => '0.00', 'cod_charge' => '0.00', 'cod_available' => true, 'estimated_delivery' => $this->staticEdd($settings)]], $postalCode, $cod, 'free');
        }
        if ($settings->mode === 'free') {
            return $this->persist($cart, [['provider' => 'E4ENGINEERS', 'courier_code' => 'FREE', 'courier_name' => 'Free shipping', 'charge' => '0.00', 'cod_charge' => '0.00', 'cod_available' => true, 'estimated_delivery' => $this->staticEdd($settings)]], $postalCode, $cod, 'free');
        }
        if ($settings->mode === 'flat_rate' || ! $settings->provider_live_rates_enabled) {
            return $this->flat($cart, $postalCode, $cod, $settings);
        }
        $pickup = ShippingPickupLocation::where('is_active', true)->orderByDesc('is_default')->first();
        if (! $pickup) {
            throw new ShippingProviderException('ORIGIN_NOT_CONFIGURED', 'Shipping origin is not configured.');
        }$request = $this->packages->calculate($cart, $discounted) + ['origin_postal_code' => $pickup->postal_code, 'destination_postal_code' => $postalCode, 'cod' => $cod];
        try {
            $rates = $this->providers->rates($request);
            foreach ($rates as &$rate) {
                $base = $this->cents((string) $rate['charge']);
                $markup = (int) round($base * (float) $settings->rate_markup_percentage / 100);
                $rate['charge'] = $this->money($base + $markup);
                $rate['cod_charge'] = $this->money($this->cents((string) ($rate['cod_charge'] ?? 0)));
            }unset($rate);
            usort($rates, fn ($a, $b) => $this->cents($a['charge']) <=> $this->cents($b['charge']));

            return $this->persist($cart, $rates, $postalCode, $cod, 'live');
        } catch (ShippingProviderException $e) {
            if ($settings->fallback_to_flat_rate && $settings->flat_shipping_enabled) {
                return $this->flat($cart, $postalCode, $cod, $settings);
            }throw $e;
        }
    }

    private function flat(Cart $cart, string $postal, bool $cod, ShippingSetting $s): array
    {
        if (! $s->flat_shipping_enabled) {
            throw new ShippingProviderException('NO_SHIPPING_RATE', 'No shipping rate is available.');
        }

return $this->persist($cart, [['provider' => 'E4ENGINEERS', 'courier_code' => 'FLAT', 'courier_name' => 'Standard shipping', 'charge' => $s->flat_shipping_charge, 'cod_charge' => '0.00', 'cod_available' => true, 'estimated_delivery' => $this->staticEdd($s)]], $postal, $cod, 'flat_rate');
    }

    private function persist(Cart $cart, array $rates, string $postal, bool $cod, string $source): array
    {
        $hash = hash('sha256', $cart->id.'|'.$cart->updated_at.'|'.$postal.'|'.($cod ? 1 : 0));
        $quotes = [];
        foreach ($rates as $rate) {
            $quote = ShippingQuote::create(['id' => (string) Str::uuid(), 'cart_id' => $cart->id, 'provider_code' => $rate['provider'], 'courier_code' => $rate['courier_code'], 'courier_name' => $rate['courier_name'], 'charge' => $rate['charge'], 'cod_charge' => $rate['cod_charge'] ?? 0, 'cod_available' => $rate['cod_available'] ?? false, 'estimated_delivery' => $rate['estimated_delivery'] ?? null, 'quoted_at' => now(), 'expires_at' => now()->addMinutes(15), 'request_hash' => $hash, 'metadata' => ['source' => $source]]);
            $quotes[] = ['quote_id' => $quote->id, 'provider' => $quote->provider_code, 'courier_code' => $quote->courier_code, 'courier_name' => $quote->courier_name, 'shipping_charge' => $quote->charge, 'cod_charge' => $quote->cod_charge, 'cod_available' => $quote->cod_available, 'estimated_delivery' => $quote->estimated_delivery, 'expires_at' => $quote->expires_at];
        }

return ['serviceable' => count($quotes) > 0, 'options' => $quotes, 'selected_quote' => $quotes[0] ?? null, 'discounted_subtotal' => $this->carts->payload($cart)['summary']['discounted_subtotal'], 'shipping_charge' => $quotes[0]['shipping_charge'] ?? null, 'payable_before_order' => $quotes ? $this->money($this->cents($this->carts->payload($cart)['summary']['discounted_subtotal']) + $this->cents($quotes[0]['shipping_charge']) + $this->cents($quotes[0]['cod_charge'])) : null];
    }

    private function staticEdd(ShippingSetting $s): string
    {
        return now()->addDays($s->handling_days + 5)->toDateString();
    }

    private function cents(string $v): int
    {
        [$w,$f] = array_pad(explode('.', $v, 2), 2, '');

        return ((int) $w * 100) + (int) str_pad(substr($f, 0, 2), 2, '0');
    }

    private function money(int $v): string
    {
        return number_format($v / 100,2,'.','');
    }
}
