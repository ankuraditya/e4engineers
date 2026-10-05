<?php

namespace App\Listeners;

use App\Enums\NotificationType;
use App\Enums\ShipmentStatus;
use App\Events\AccountPasswordSetupRequested;
use App\Events\InvoiceIssued;
use App\Events\OrderPlaced;
use App\Events\PaymentFailed;
use App\Events\PaymentSucceeded;
use App\Events\ShipmentCreated;
use App\Events\ShipmentStatusChanged;
use App\Models\Order;
use App\Services\Notifications\NotificationManager;
use Illuminate\Events\Dispatcher;

class TransactionalNotificationSubscriber
{
    public function __construct(private NotificationManager $notifications) {}

    public function order(OrderPlaced $event): void
    {
        $order = $event->order->loadMissing(['user', 'shippingAddress']);
        if ($order->payment_method === 'cod') {
            $this->sendOrder($order);
        }
    }

    public function setup(AccountPasswordSetupRequested $event): void
    {
        $url = rtrim((string) config('e4engineers.frontend_url'), '/').'/reset-password?'.http_build_query(['token' => $event->token, 'email' => $event->user->email]);
        $this->notifications->dispatch(NotificationType::PasswordSetup, $event->user->email, ['customer_name' => $event->user->name, 'setup_url' => $url], 'password-setup:'.$event->user->id.':'.hash('sha256', $event->token), $event->user->id, 'user', $event->user->id);
    }

    public function paid(PaymentSucceeded $event): void
    {
        $attempt = $event->attempt->loadMissing(['order.user', 'provider']);
        $order = $attempt->order;
        $this->sendOrder($order);
        $this->notifications->dispatch(NotificationType::PaymentSuccess, $this->email($order), ['order_number' => $order->order_number, 'amount_paid' => $order->currency.' '.$order->grand_total, 'gateway' => $attempt->provider->name, 'payment_reference' => $attempt->provider_payment_id, 'order_url' => $this->orderUrl($order), 'invoice_url' => $this->invoiceUrl($order)], 'payment-success:'.$attempt->id, $order->user_id, 'order', $order->id);
    }

    public function failed(PaymentFailed $event): void
    {
        $attempt = $event->attempt->loadMissing('order.user');
        $order = $attempt->order;
        $this->notifications->dispatch(NotificationType::PaymentFailed, $this->email($order), ['order_number' => $order->order_number, 'retry_url' => $this->orderUrl($order)], 'payment-failed:'.$attempt->id, $order->user_id, 'order', $order->id);
    }

    public function invoice(InvoiceIssued $event): void
    {
        $invoice = $event->invoice->loadMissing('order.user');
        $order = $invoice->order;
        $this->notifications->dispatch(NotificationType::InvoiceReady, $this->email($order), ['order_number' => $order->order_number, 'invoice_url' => $this->invoiceUrl($order)], 'invoice-ready:'.$invoice->id, $order->user_id, 'invoice', $invoice->id);
    }

    public function shipment(ShipmentCreated $event): void
    {
        $this->sendShipment(NotificationType::ShipmentCreated, $event->shipment);
    }

    public function shipmentStatus(ShipmentStatusChanged $event): void
    {
        $type = match ($event->to) {
            ShipmentStatus::AwbAssigned => NotificationType::AwbAssigned,ShipmentStatus::PickupScheduled => NotificationType::PickupScheduled,ShipmentStatus::PickedUp => NotificationType::ShipmentPickedUp,ShipmentStatus::InTransit => NotificationType::ShipmentInTransit,ShipmentStatus::OutForDelivery => NotificationType::OutForDelivery,ShipmentStatus::Delivered => NotificationType::Delivered,ShipmentStatus::DeliveryFailed => NotificationType::DeliveryFailed,ShipmentStatus::RtoInitiated => NotificationType::RtoInitiated,ShipmentStatus::RtoDelivered => NotificationType::RtoDelivered,default => null
        };
        if ($type) {
            $this->sendShipment($type, $event->shipment);
        }
    }

    private function sendOrder(Order $order): void
    {
        $this->notifications->dispatch(NotificationType::OrderConfirmed, $this->email($order), ['customer_name' => $order->shippingAddress?->name ?? $order->user?->name ?? 'Engineer', 'order_number' => $order->order_number, 'order_total' => $order->currency.' '.$order->grand_total, 'payment_method' => $order->payment_method === 'cod' ? 'Cash on Delivery' : strtoupper($order->payment_method), 'order_url' => $this->orderUrl($order)], 'order-confirmed:'.$order->id, $order->user_id, 'order', $order->id);
    }

    private function sendShipment(NotificationType $type, $shipment): void
    {
        $shipment->loadMissing(['order.user', 'provider']);
        $order = $shipment->order;
        $this->notifications->dispatch($type, $this->email($order), ['order_number' => $order->order_number, 'courier_name' => $shipment->courier_name ?: $shipment->provider->name, 'tracking_number' => $shipment->awb_number, 'tracking_url' => $this->trackingUrl($order)], $type->value.':'.$shipment->id, $order->user_id, 'shipment', $shipment->id);
    }

    private function email(Order $order): string
    {
        return (string) ($order->user?->email ?: $order->guest_email ?: $order->shippingAddress?->email);
    }

    private function orderUrl(Order $order): string
    {
        return rtrim((string) config('e4engineers.frontend_url'), '/').'/account/orders/'.$order->order_number;
    }

    private function trackingUrl(Order $order): string
    {
        return $this->orderUrl($order).'/track';
    }

    private function invoiceUrl(Order $order): string
    {
        return rtrim((string) config('e4engineers.frontend_url'), '/').'/api/v1/orders/'.$order->order_number.'/invoice/download';
    }

    public function subscribe(Dispatcher $events): array
    {
        return [OrderPlaced::class => 'order', AccountPasswordSetupRequested::class => 'setup', PaymentSucceeded::class => 'paid', PaymentFailed::class => 'failed', InvoiceIssued::class => 'invoice', ShipmentCreated::class => 'shipment', ShipmentStatusChanged::class => 'shipmentStatus'];
    }
}
