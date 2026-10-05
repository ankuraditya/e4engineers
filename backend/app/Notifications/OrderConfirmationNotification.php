<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = $notifiable instanceof User ? $notifiable->name : 'Engineer';

        return (new MailMessage)->subject("Order {$this->order->order_number} confirmed")
            ->greeting("Hello {$name},")
            ->line("Your cash-on-delivery order {$this->order->order_number} has been confirmed.")
            ->line("Order total: ₹{$this->order->grand_total}");
    }
}
