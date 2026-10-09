<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Exceptions\ShippingProviderException;
use App\Http\Controllers\Controller;
use App\Models\ShippingAuditLog;
use App\Models\ShippingPickupLocation;
use App\Models\ShippingProvider;
use App\Models\ShippingSetting;
use App\Services\Shipping\ShippingProviderManager;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ShippingController extends Controller
{
    use ApiResponse;

    public function __construct(private ShippingProviderManager $manager) {}

    public function providers(): JsonResponse
    {
        Gate::authorize('shipping.providers.view');

        return $this->successResponse(ShippingProvider::orderBy('priority')->get()->map(fn ($p) => $this->providerData($p)));
    }

    public function provider(ShippingProvider $provider): JsonResponse
    {
        Gate::authorize('shipping.providers.view');

        return $this->successResponse($this->providerData($provider));
    }

    public function credentials(Request $r, ShippingProvider $provider): JsonResponse
    {
        Gate::authorize('shipping.providers.configure');
        $adapter = $this->manager->adapter($provider->code);
        $rules = [];
        foreach ($adapter->credentialFields() as $f) {
            $rules[$f['key']] = [$f['required'] && ! ($provider->configuration[$f['key']] ?? null) ? 'required' : 'nullable', $f['type'] === 'email' ? 'email' : 'string', 'max:500'];
        }$data = $r->validate($rules);
        $config = $provider->configuration ?? [];
        foreach ($data as $key => $value) {
            if ($value !== null && $value !== '') {
                $config[$key] = $value;
            }
        }$provider->update(['configuration' => $config, 'connection_status' => 'not_tested', 'last_connection_error' => null]);
        Cache::forget('shipping:'.strtolower($provider->code).':'.$provider->id.':auth-token');
        $this->audit($r, $provider, 'credentials_updated');

        return $this->successResponse($this->providerData($provider->refresh()), 'Credentials updated securely.');
    }

    public function clearCredentials(Request $r, ShippingProvider $provider): JsonResponse
    {
        Gate::authorize('shipping.providers.configure');
        $provider->update(['configuration' => null, 'is_enabled' => false, 'is_default' => false, 'connection_status' => 'not_configured']);
        $this->audit($r, $provider, 'credentials_cleared');

        return $this->successResponse($this->providerData($provider->refresh()), 'Credentials cleared.');
    }

    public function test(Request $r, ShippingProvider $provider): JsonResponse
    {
        Gate::authorize('shipping.providers.test');
        try {
            $result = $this->manager->adapter($provider->code)->testConnection($provider);
            $provider->update(['connection_status' => 'connected', 'last_connection_test_at' => now(), 'last_connection_error' => null]);
        } catch (ShippingProviderException $e) {
            $result = ['connected' => false, 'provider' => $provider->code, 'message' => $e->getMessage()];
            $provider->update(['connection_status' => 'error', 'last_connection_test_at' => now(), 'last_connection_error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            $result = ['connected' => false, 'provider' => $provider->code, 'message' => 'Connection failed.'];
            $provider->update(['connection_status' => 'error', 'last_connection_test_at' => now(), 'last_connection_error' => 'Authentication or provider connection failed.']);
        }$this->audit($r, $provider, 'connection_tested', ['connected' => $result['connected']]);

        return $this->successResponse($result, $result['message']);
    }

    public function toggle(Request $r, ShippingProvider $provider): JsonResponse
    {
        Gate::authorize('shipping.providers.toggle');
        $d = $r->validate(['enabled' => ['required', 'boolean']]);
        if ($d['enabled'] && $provider->connection_status !== 'connected') {
            return $this->errorResponse('Test and verify provider credentials before enabling.', ['enabled' => ['Provider is not connected.']], 422);
        }if (! $d['enabled'] && $provider->is_default) {
            return $this->errorResponse('Choose another default provider before disabling this provider.', status: 422);
        }$provider->update(['is_enabled' => $d['enabled']]);
        $this->audit($r, $provider, $d['enabled'] ? 'provider_enabled' : 'provider_disabled');

        return $this->successResponse($this->providerData($provider->refresh()));
    }

    public function makeDefault(Request $r, ShippingProvider $provider): JsonResponse
    {
        Gate::authorize('shipping.settings.update');
        if (! $provider->is_enabled) {
            return $this->errorResponse('Only an enabled provider may be the default.', status: 422);
        }DB::transaction(function () use ($provider) {
            ShippingProvider::query()->update(['is_default' => false]);
            $provider->update(['is_default' => true]);
            ShippingSetting::current()->update(['default_provider_id' => $provider->id]);
        });
        $this->audit($r, $provider, 'default_provider_changed');

        return $this->successResponse($this->providerData($provider->refresh()));
    }

    public function priority(Request $r, ShippingProvider $provider): JsonResponse
    {
        Gate::authorize('shipping.settings.update');
        $d = $r->validate(['priority' => ['required', 'integer', 'min:1', 'max:1000']]);
        $provider->update($d);
        $this->audit($r, $provider, 'priority_changed');

        return $this->successResponse($this->providerData($provider));
    }

    public function settings(): JsonResponse
    {
        Gate::authorize('shipping.settings.view');

        return $this->successResponse(ShippingSetting::current()->load(['defaultProvider', 'fallbackProvider']));
    }

    public function updateSettings(Request $r): JsonResponse
    {
        Gate::authorize('shipping.settings.update');
        $d = $r->validate(['self_collect_enabled' => 'sometimes|boolean', 'self_collect_location_id' => 'nullable|exists:shipping_pickup_locations,id', 'self_collect_hours' => 'nullable|string|max:255', 'shipping_enabled' => 'sometimes|boolean', 'mode' => 'sometimes|in:live_provider,flat_rate,free,hybrid', 'fallback_provider_id' => 'nullable|exists:shipping_providers,id', 'automatic_fallback' => 'sometimes|boolean', 'fallback_to_flat_rate' => 'sometimes|boolean', 'free_shipping_enabled' => 'sometimes|boolean', 'free_shipping_threshold' => 'nullable|numeric|min:0', 'flat_shipping_enabled' => 'sometimes|boolean', 'flat_shipping_charge' => 'sometimes|numeric|min:0', 'provider_live_rates_enabled' => 'sometimes|boolean', 'show_delivery_estimate' => 'sometimes|boolean', 'default_package_weight_grams' => 'sometimes|integer|min:1', 'default_length_cm' => 'sometimes|numeric|gt:0', 'default_width_cm' => 'sometimes|numeric|gt:0', 'default_height_cm' => 'sometimes|numeric|gt:0', 'handling_days' => 'sometimes|integer|min:0|max:30', 'rate_markup_percentage' => 'sometimes|numeric|min:0|max:100', 'automatic_shipment_creation' => 'sometimes|boolean', 'automatic_awb_assignment' => 'sometimes|boolean', 'automatic_pickup_scheduling' => 'sometimes|boolean', 'automatic_label_generation' => 'sometimes|boolean', 'automatic_manifest_generation' => 'sometimes|boolean', 'default_pickup_location_id' => 'nullable|exists:shipping_pickup_locations,id', 'tracking_sync_minutes' => 'sometimes|integer|min:5|max:1440']);
        $settings = ShippingSetting::current();
        if (isset($d['fallback_provider_id']) && $d['fallback_provider_id'] === $settings->default_provider_id) {
            return $this->errorResponse('Fallback provider must differ from default provider.', status: 422);
        }
        if ($d['self_collect_enabled'] ?? $settings->self_collect_enabled) {
            $locationId = $d['self_collect_location_id'] ?? $settings->self_collect_location_id;
            $hours = $d['self_collect_hours'] ?? $settings->self_collect_hours;
            if (! $locationId || blank($hours) || ! ShippingPickupLocation::query()->whereKey($locationId)->where('is_active', true)->exists()) {
                return $this->errorResponse('Choose an active collection location and add collection hours before enabling Self Collect.', status: 422);
            }
        }
        $settings->update($d);
        $this->audit($r, null, 'shipping_settings_updated');

        return $this->successResponse($settings->refresh(), 'Shipping settings updated.');
    }

    public function pickups(): JsonResponse
    {
        Gate::authorize('shipping.settings.view');

        return $this->successResponse(ShippingPickupLocation::orderByDesc('is_default')->get());
    }

    public function savePickup(Request $r, ?ShippingPickupLocation $pickup = null): JsonResponse
    {
        Gate::authorize('shipping.settings.update');
        $d = $r->validate(['name' => 'required|string|max:100', 'company_name' => 'nullable|string|max:150', 'contact_name' => 'required|string|max:100', 'phone' => 'required|string|max:20', 'email' => 'nullable|email', 'address_line1' => 'required|string|max:255', 'address_line2' => 'nullable|string|max:255', 'city' => 'required|string|max:100', 'state' => 'required|string|max:100', 'postal_code' => 'required|regex:/^[1-9][0-9]{5}$/', 'country' => 'sometimes|string|size:2', 'is_default' => 'sometimes|boolean', 'is_active' => 'sometimes|boolean']);
        $pickup ??= new ShippingPickupLocation;
        if ($d['is_default'] ?? false) {
            ShippingPickupLocation::query()->update(['is_default' => false]);
        }$pickup->fill($d)->save();
        $this->audit($r, null, 'pickup_saved');

        return $this->successResponse($pickup, 'Pickup location saved.', $pickup->wasRecentlyCreated ? 201 : 200);
    }

    private function providerData(ShippingProvider $p): array
    {
        return ['id' => $p->id, 'code' => $p->code, 'name' => $p->name, 'is_enabled' => $p->is_enabled, 'is_default' => $p->is_default, 'priority' => $p->priority, 'connection_status' => $p->connection_status, 'last_connection_test_at' => $p->last_connection_test_at, 'last_connection_error' => $p->last_connection_error, 'credential_fields' => $this->manager->adapter($p->code)->credentialFields(), 'configuration' => $p->maskedConfiguration()];
    }

    private function audit(Request $r, ?ShippingProvider $p, string $event, array $context = []): void
    {
        ShippingAuditLog::create(['user_id' => $r->user()?->id, 'shipping_provider_id' => $p?->id, 'event' => $event, 'context' => $context]);
    }
}
