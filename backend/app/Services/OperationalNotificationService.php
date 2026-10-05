<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Events\OperationalNotificationRequested;
use App\Models\EmailSetting;

class OperationalNotificationService
{
    public function customer(NotificationType $type, object $record, string $email, array $context): void
    {
        OperationalNotificationRequested::dispatch($type, $email, $context, $type->value.':'.$record->id, $record->user_id ?? null, class_basename($record), $record->id);
    }

    public function admin(NotificationType $type, object $record, array $context): void
    {
        $email = EmailSetting::current()->reply_to_email ?: EmailSetting::current()->from_email;
        if ($email) {
            OperationalNotificationRequested::dispatch($type, $email, $context, $type->value.':'.$record->id, null, class_basename($record), $record->id);
        }
    }
}
