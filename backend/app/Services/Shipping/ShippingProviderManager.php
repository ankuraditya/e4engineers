<?php

namespace App\Services\Shipping;

use App\Contracts\ShippingProviderInterface;
use App\Exceptions\ShippingProviderException;
use App\Models\ShippingProvider;
use App\Models\ShippingSetting;

class ShippingProviderManager
{
    public function __construct(private NimbusPostShippingProvider $nimbus, private ShiprocketShippingProvider $shiprocket) {}

    public function adapter(string $code): ShippingProviderInterface
    {
        return match (strtoupper($code)) {
            'NIMBUSPOST' => $this->nimbus,'SHIPROCKET' => $this->shiprocket,default => throw new ShippingProviderException('UNKNOWN_PROVIDER', 'Unknown shipping provider.')
        };
    }

    public function enabled(): array
    {
        return ShippingProvider::where('is_enabled', true)->orderBy('priority')->get()->all();
    }

    public function rates(array $request): array
    {
        $settings = ShippingSetting::current();
        $providers = [];
        $primary = $settings->defaultProvider;
        if ($primary?->is_enabled) {
            $providers[] = $primary;
        }if ($settings->automatic_fallback && $settings->fallbackProvider?->is_enabled && $settings->fallback_provider_id !== $settings->default_provider_id) {
            $providers[] = $settings->fallbackProvider;
        }foreach ($this->enabled() as $p) {
            if (! collect($providers)->contains('id', $p->id)) {
                $providers[] = $p;
            }
        }$last = null;
        foreach ($providers as $provider) {
            try {
                $rates = $this->adapter($provider->code)->rates($provider, $request);
                if ($rates !== []) {
                    return $rates;
                }
            } catch (ShippingProviderException $e) {
                $last = $e;
                if (! $settings->automatic_fallback) {
                    throw $e;
                }
            }
        }throw $last ?? new ShippingProviderException('NO_PROVIDER_AVAILABLE', 'No enabled shipping provider is available.');
    }
}
