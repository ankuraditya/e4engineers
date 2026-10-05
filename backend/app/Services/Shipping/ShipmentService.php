<?php

namespace App\Services\Shipping;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;
use App\Enums\ShippingStatus;
use App\Events\ShipmentCreated;
use App\Events\ShipmentDelivered;
use App\Events\ShipmentStatusChanged;
use App\Exceptions\ShippingProviderException;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShipmentAttempt;
use App\Models\ShipmentTrackingEvent;
use App\Models\ShippingPickupLocation;
use App\Models\ShippingProvider;
use App\Models\ShippingSetting;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ShipmentService
{
    public function __construct(private ShippingProviderManager $providers) {}

    public function create(Order $order, ?string $providerCode = null, ?string $courierCode = null, ?int $pickupId = null): Shipment
    {
        if ($existing = Shipment::where('order_id', $order->id)->first()) {
            return $existing->load(['provider', 'trackingEvents']);
        }
        $this->ensureEligible($order);
        $snapshotProvider = data_get($order->shipping_snapshot, 'provider');
        $code = strtoupper($providerCode ?: $snapshotProvider ?: '');
        $provider = ShippingProvider::where('code', $code)->where('is_enabled', true)->where('connection_status', 'connected')->first();
        if (! $provider) {
            throw ValidationException::withMessages(['provider' => ['Selected shipping provider is unavailable.']]);
        }
        $pickup = $pickupId ? ShippingPickupLocation::where('is_active', true)->findOrFail($pickupId) : ShippingPickupLocation::where('is_default', true)->where('is_active', true)->first();
        if (! $pickup) {
            throw ValidationException::withMessages(['pickup_location' => ['Configure an active pickup location.']]);
        }
        $package = $this->package($order);
        try {
            $shipment = Shipment::create([
                'order_id' => $order->id, 'shipping_provider_id' => $provider->id,
                'courier_code' => $courierCode ?: data_get($order->shipping_snapshot, 'courier_code'),
                'courier_name' => data_get($order->shipping_snapshot, 'courier_name'), 'status' => ShipmentStatus::Booking,
                'payment_mode' => $order->payment_method === 'cod' ? 'COD' : 'PREPAID',
                'cod_amount' => $order->payment_method === 'cod' ? $order->grand_total : 0,
                'shipping_charge_snapshot' => $order->shipping_total, 'weight_grams' => $package['weight_grams'],
                'length_cm' => $package['length_cm'], 'width_cm' => $package['width_cm'], 'height_cm' => $package['height_cm'],
                'pickup_location_snapshot' => $pickup->toArray(),
            ]);
        } catch (UniqueConstraintViolationException) {
            return Shipment::where('order_id', $order->id)->firstOrFail();
        }
        $attempt = $this->attempt($shipment, 'create', 'shipment:'.$order->id.':'.$provider->code);
        try {
            $result = $this->providers->adapter($provider->code)->createShipment($provider, $this->payload($order, $shipment));
            $shipment->update(['provider_order_id' => $result['provider_order_id'] ?? null, 'provider_shipment_id' => $result['provider_shipment_id'] ?? null, 'awb_number' => $result['awb_number'] ?? null, 'courier_name' => $result['courier_name'] ?? $shipment->courier_name, 'status' => filled($result['awb_number'] ?? null) ? ShipmentStatus::AwbAssigned : ShipmentStatus::Booked]);
            $attempt->update(['status' => 'succeeded', 'provider_reference' => $shipment->provider_shipment_id, 'completed_at' => now()]);
            $this->recordEvent($shipment, $shipment->status, 'Shipment booked', now(), null, 'system');
            $this->syncOrder($shipment);
            ShipmentCreated::dispatch($shipment->fresh());
        } catch (ConnectionException $exception) {
            $attempt->update(['status' => 'unknown', 'failure_code' => 'PROVIDER_TIMEOUT', 'failure_message' => 'Provider result is unknown; reconcile before retrying.', 'completed_at' => now()]);
            $shipment->update(['status' => ShipmentStatus::Unknown, 'failure_code' => 'PROVIDER_TIMEOUT', 'failure_message' => 'Booking outcome requires reconciliation.']);
            throw $exception;
        } catch (\Throwable $exception) {
            $attempt->update(['status' => 'failed', 'failure_code' => 'BOOKING_FAILED', 'failure_message' => 'Provider booking failed.', 'completed_at' => now()]);
            $shipment->update(['status' => ShipmentStatus::BookingFailed, 'failure_code' => 'BOOKING_FAILED', 'failure_message' => 'Provider booking failed.', 'failed_at' => now()]);
            throw $exception;
        }

        return $shipment->refresh()->load(['provider', 'trackingEvents']);
    }

    public function assignAwb(Shipment $shipment): Shipment
    {
        if ($shipment->awb_number) {
            return $shipment;
        }
        $result = $this->providers->adapter($shipment->provider->code)->assignAwb($shipment->provider, $shipment->toArray());
        $shipment->update(['awb_number' => $result['awb_number'], 'courier_name' => $result['courier_name'] ?? $shipment->courier_name, 'status' => ShipmentStatus::AwbAssigned]);
        $this->recordEvent($shipment, ShipmentStatus::AwbAssigned, 'AWB assigned', now(), null, 'admin');
        $this->syncOrder($shipment);
        ShipmentStatusChanged::dispatch($shipment->fresh(), ShipmentStatus::Booked, ShipmentStatus::AwbAssigned);

        return $shipment->refresh();
    }

    public function changeCourier(Shipment $shipment, string $courierCode, ?string $courierName = null): Shipment
    {
        if ($shipment->awb_number || ! in_array($shipment->status, [ShipmentStatus::Booked, ShipmentStatus::BookingFailed], true)) {
            throw ValidationException::withMessages(['courier' => ['Courier can only be changed before AWB assignment.']]);
        }
        $shipment->update(['courier_code' => $courierCode, 'courier_name' => $courierName]);

        return $shipment->refresh();
    }

    public function pickup(Shipment $shipment): Shipment
    {
        if ($shipment->status === ShipmentStatus::PickupScheduled) {
            return $shipment;
        }
        $this->providers->adapter($shipment->provider->code)->schedulePickup($shipment->provider, $shipment->toArray());
        $shipment->update(['status' => ShipmentStatus::PickupScheduled]);
        $this->recordEvent($shipment, ShipmentStatus::PickupScheduled, 'Pickup scheduled', now(), null, 'admin');
        ShipmentStatusChanged::dispatch($shipment->fresh(), ShipmentStatus::AwbAssigned, ShipmentStatus::PickupScheduled);

        return $shipment->refresh();
    }

    public function document(Shipment $shipment, string $type): Shipment
    {
        $column = $type.'_path';
        if ($shipment->{$column} && Storage::disk('local')->exists($shipment->{$column})) {
            return $shipment;
        }
        $method = $type === 'label' ? 'generateLabel' : 'generateManifest';
        $result = $this->providers->adapter($shipment->provider->code)->{$method}($shipment->provider, $shipment->toArray());
        $content = $result['content'] ?? null;
        if (! $content && filter_var($result['url'] ?? null, FILTER_VALIDATE_URL) && parse_url($result['url'], PHP_URL_SCHEME) === 'https') {
            $response = Http::timeout(20)->get($result['url']);
            if ($response->successful()) {
                $content = $response->body();
            }
        }
        if (! $content) {
            throw new ShippingProviderException('DOCUMENT_FAILED', ucfirst($type).' could not be generated.');
        }
        $path = 'shipments/'.$shipment->id.'/'.$type.'.pdf';
        Storage::disk('local')->put($path, $content);
        $shipment->update([$column => $path]);

        return $shipment->refresh();
    }

    public function refreshTracking(Shipment $shipment): Shipment
    {
        $reference = $shipment->awb_number ?: $shipment->provider_shipment_id ?: $shipment->provider_order_id ?: $shipment->order->order_number;
        $result = $this->providers->adapter($shipment->provider->code)->trackShipment($shipment->provider, $reference);
        $shipment->update(array_filter([
            'awb_number' => data_get($result, 'awb_number') ?? data_get($result, 'data.awb_number') ?? data_get($result, 'tracking_data.shipment_track.0.awb_code'),
            'provider_shipment_id' => data_get($result, 'shipment_id') ?? data_get($result, 'data.shipment_id'),
            'provider_order_id' => data_get($result, 'order_id') ?? data_get($result, 'data.order_id'),
        ]));
        $rawStatus = (string) (data_get($result, 'tracking_data.shipment_track.0.current_status') ?? data_get($result, 'data.status') ?? data_get($result, 'status') ?? 'UNKNOWN');
        $events = data_get($result, 'tracking_data.shipment_track_activities', data_get($result, 'data.events', []));
        foreach ($events ?: [] as $event) {
            $this->handleTracking($shipment, (string) ($event['status'] ?? $event['activity'] ?? $rawStatus), (string) ($event['activity'] ?? $event['status'] ?? 'Tracking update'), $event['date'] ?? $event['time'] ?? now(), $event['location'] ?? null, 'poll');
        }
        $this->handleTracking($shipment, $rawStatus, $rawStatus, now(), null, 'poll');
        $shipment->update(['last_tracked_at' => now()]);

        return $shipment->refresh()->load('trackingEvents');
    }

    public function handleTracking(Shipment $shipment, string $providerStatus, string $label, mixed $occurredAt, ?string $location, string $source, ?string $eventId = null): Shipment
    {
        $status = $this->normalize($providerStatus);
        $time = Carbon::parse($occurredAt);
        $this->recordEvent($shipment, $status, $label, $time, $location, $source, $providerStatus, $eventId);
        $latest = $shipment->trackingEvents()->reorder()->latest('occurred_at')->first();
        if ($latest && $latest->occurred_at->isSameSecond($time) && $this->rank($status) >= $this->rank($shipment->status)) {
            $updates = ['status' => $status, 'provider_status' => $providerStatus, 'last_tracked_at' => now()];
            if ($status === ShipmentStatus::PickedUp) {
                $updates += ['picked_up_at' => $time, 'shipped_at' => $time];
            }
            if ($status === ShipmentStatus::Delivered) {
                $updates['delivered_at'] = $time;
            }
            $previous = $shipment->status;
            $shipment->update($updates);
            $this->syncOrder($shipment);
            if ($previous !== $status) {
                ShipmentStatusChanged::dispatch($shipment->fresh(), $previous, $status);
                if ($status === ShipmentStatus::Delivered) {
                    ShipmentDelivered::dispatch($shipment->fresh());
                }
            }
        }

        return $shipment->refresh();
    }

    public function cancel(Shipment $shipment): Shipment
    {
        if ($shipment->status === ShipmentStatus::Cancelled) {
            return $shipment;
        }
        if (in_array($shipment->status, [ShipmentStatus::PickedUp, ShipmentStatus::InTransit, ShipmentStatus::OutForDelivery, ShipmentStatus::Delivered], true)) {
            throw ValidationException::withMessages(['shipment' => ['Shipment can no longer be cancelled.']]);
        }
        $this->providers->adapter($shipment->provider->code)->cancelShipment($shipment->provider, $shipment->provider_order_id ?: $shipment->provider_shipment_id);
        $shipment->update(['status' => ShipmentStatus::Cancelled, 'cancelled_at' => now()]);
        $this->recordEvent($shipment, ShipmentStatus::Cancelled, 'Shipment cancelled', now(), null, 'admin');
        $this->syncOrder($shipment);

        return $shipment->refresh();
    }

    public function retry(Shipment $shipment): Shipment
    {
        if ($shipment->status === ShipmentStatus::Unknown) {
            throw ValidationException::withMessages(['shipment' => ['Reconcile an unknown booking result before retrying.']]);
        }
        if ($shipment->status !== ShipmentStatus::BookingFailed) {
            return $shipment;
        }
        $order = $shipment->order;
        $this->ensureEligible($order);
        $attempt = $this->attempt($shipment, 'create', 'shipment:'.$order->id.':'.$shipment->provider->code.':retry:'.($shipment->attempts()->count() + 1));
        try {
            $result = $this->providers->adapter($shipment->provider->code)->createShipment($shipment->provider, $this->payload($order, $shipment));
            $shipment->update(['provider_order_id' => $result['provider_order_id'] ?? null, 'provider_shipment_id' => $result['provider_shipment_id'] ?? null, 'awb_number' => $result['awb_number'] ?? null, 'courier_name' => $result['courier_name'] ?? $shipment->courier_name, 'status' => filled($result['awb_number'] ?? null) ? ShipmentStatus::AwbAssigned : ShipmentStatus::Booked, 'failure_code' => null, 'failure_message' => null, 'failed_at' => null]);
            $attempt->update(['status' => 'succeeded', 'provider_reference' => $shipment->provider_shipment_id, 'completed_at' => now()]);
            $this->recordEvent($shipment, $shipment->status, 'Shipment booking retried', now(), null, 'admin');
            $this->syncOrder($shipment);
        } catch (ConnectionException $exception) {
            $attempt->update(['status' => 'unknown', 'failure_code' => 'PROVIDER_TIMEOUT', 'failure_message' => 'Provider result is unknown; reconcile before retrying.', 'completed_at' => now()]);
            $shipment->update(['status' => ShipmentStatus::Unknown, 'failure_code' => 'PROVIDER_TIMEOUT', 'failure_message' => 'Booking outcome requires reconciliation.']);
            throw $exception;
        } catch (\Throwable $exception) {
            $attempt->update(['status' => 'failed', 'failure_code' => 'BOOKING_FAILED', 'failure_message' => 'Provider booking failed.', 'completed_at' => now()]);
            throw $exception;
        }

        return $shipment->refresh()->load(['provider', 'trackingEvents']);
    }

    private function ensureEligible(Order $order): void
    {
        $eligible = $order->status === OrderStatus::Confirmed && $order->shippingAddress && ($order->payment_method === 'cod' ? $order->payment_status === PaymentStatus::CodPending : $order->payment_status === PaymentStatus::Paid);
        if (! $eligible) {
            throw ValidationException::withMessages(['order' => ['Order is not eligible for shipment.']]);
        }
    }

    private function package(Order $order): array
    {
        $settings = ShippingSetting::current();
        $order->loadMissing('items.book');
        $weight = 0;
        foreach ($order->items as $item) {
            $weight += ($item->book?->weight_grams ?: $settings->default_package_weight_grams) * $item->quantity;
        }

        return ['weight_grams' => max($weight, $settings->default_package_weight_grams), 'length_cm' => $settings->default_length_cm, 'width_cm' => $settings->default_width_cm, 'height_cm' => $settings->default_height_cm];
    }

    private function payload(Order $order, Shipment $shipment): array
    {
        $order->loadMissing(['items', 'shippingAddress']);
        $a = $order->shippingAddress;
        $p = $shipment->pickup_location_snapshot;

        return ['order_id' => $order->order_number, 'order_number' => $order->order_number, 'order_date' => $order->placed_at?->format('Y-m-d H:i'), 'pickup_location' => $p['name'], 'billing_customer_name' => $a->name, 'billing_address' => $a->address_line1, 'billing_address_2' => $a->address_line2, 'billing_city' => $a->city, 'billing_pincode' => $a->postal_code, 'billing_state' => $a->state, 'billing_country' => $a->country, 'billing_email' => $a->email, 'billing_phone' => $a->mobile, 'shipping_is_billing' => true, 'order_items' => $order->items->map(fn ($item) => ['name' => $item->title, 'sku' => $item->sku, 'units' => $item->quantity, 'selling_price' => $item->unit_price])->all(), 'payment_method' => $shipment->payment_mode, 'sub_total' => $order->grand_total, 'cod_amount' => $shipment->cod_amount, 'length' => $shipment->length_cm, 'breadth' => $shipment->width_cm, 'height' => $shipment->height_cm, 'weight' => $shipment->weight_grams / 1000, 'courier_id' => $shipment->courier_code];
    }

    private function attempt(Shipment $shipment, string $action, string $key): ShipmentAttempt
    {
        return ShipmentAttempt::create(['id' => (string) Str::uuid(), 'shipment_id' => $shipment->id, 'provider' => $shipment->provider->code, 'attempt_number' => $shipment->attempts()->where('action', $action)->count() + 1, 'action' => $action, 'idempotency_key' => $key, 'status' => 'processing', 'started_at' => now()]);
    }

    private function recordEvent(Shipment $shipment, ShipmentStatus $status, string $label, mixed $time, ?string $location, string $source, ?string $providerStatus = null, ?string $eventId = null): void
    {
        $hash = hash('sha256', implode('|', [$eventId, $status->value, (string) $time, $location, $label]));
        ShipmentTrackingEvent::firstOrCreate(['shipment_id' => $shipment->id, 'event_hash' => $hash], ['provider_event_id' => $eventId, 'status' => $status, 'provider_status' => $providerStatus, 'label' => $label, 'location' => $location, 'occurred_at' => $time, 'source' => $source]);
    }

    public function normalize(string $status): ShipmentStatus
    {
        $value = strtoupper(str_replace(['-', '_'], ' ', $status));

        return match (true) {
            str_contains($value, 'RTO DELIVER') => ShipmentStatus::RtoDelivered, str_contains($value, 'RTO') && str_contains($value, 'TRANSIT') => ShipmentStatus::RtoInTransit, str_contains($value, 'RTO') => ShipmentStatus::RtoInitiated, str_contains($value, 'OUT FOR DELIVERY') => ShipmentStatus::OutForDelivery, str_contains($value, 'FAIL') || str_contains($value, 'UNDELIVER') => ShipmentStatus::DeliveryFailed, str_contains($value, 'DELIVERED') => ShipmentStatus::Delivered, str_contains($value, 'PICK') => ShipmentStatus::PickedUp, str_contains($value, 'IN TRANSIT') => ShipmentStatus::InTransit, str_contains($value, 'CANCEL') => ShipmentStatus::Cancelled, default => ShipmentStatus::Unknown
        };
    }

    private function rank(ShipmentStatus $status): int
    {
        return match ($status) {
            ShipmentStatus::Pending => 0, ShipmentStatus::Booking, ShipmentStatus::BookingFailed, ShipmentStatus::Unknown => 1, ShipmentStatus::Booked => 2, ShipmentStatus::AwbAssigned => 3, ShipmentStatus::PickupScheduled => 4, ShipmentStatus::PickedUp => 5, ShipmentStatus::InTransit => 6, ShipmentStatus::OutForDelivery, ShipmentStatus::DeliveryFailed => 7, ShipmentStatus::Delivered => 8, ShipmentStatus::RtoInitiated => 9, ShipmentStatus::RtoInTransit => 10, ShipmentStatus::RtoDelivered => 11, ShipmentStatus::Cancelled => 12
        };
    }

    private function syncOrder(Shipment $shipment): void
    {
        $status = match ($shipment->status) {
            ShipmentStatus::Cancelled => ShippingStatus::Cancelled, ShipmentStatus::Delivered => ShippingStatus::Delivered, ShipmentStatus::PickedUp, ShipmentStatus::InTransit, ShipmentStatus::OutForDelivery, ShipmentStatus::DeliveryFailed, ShipmentStatus::RtoInitiated, ShipmentStatus::RtoInTransit, ShipmentStatus::RtoDelivered => ShippingStatus::Shipped, default => ShippingStatus::ReadyToShip
        };
        $shipment->order()->update(['shipping_status' => $status]);
    }
}
