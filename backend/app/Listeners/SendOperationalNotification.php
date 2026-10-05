<?php

namespace App\Listeners;

use App\Events\OperationalNotificationRequested;
use App\Services\Notifications\NotificationManager;

class SendOperationalNotification
{
    public function __construct(private NotificationManager $notifications) {}

    public function handle(OperationalNotificationRequested $event): void
    {
        $this->notifications->dispatch($event->type, $event->recipient, $event->context, $event->key, $event->userId, $event->referenceType, $event->referenceId);
    }
}
