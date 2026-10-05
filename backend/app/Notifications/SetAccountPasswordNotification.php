<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SetAccountPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = rtrim((string) config('app.frontend_url', config('app.url')), '/').'/reset-password?token='.urlencode($this->token).'&email='.urlencode($notifiable->email);

        return (new MailMessage)->subject('Set up your E4ENGINEERS account password')
            ->greeting("Welcome, {$notifiable->name}!")
            ->line('We created your account after your order was placed. Set a password to access your orders.')
            ->action('Set my password', $url)
            ->line('You were never assigned a plaintext or default password.');
    }
}
