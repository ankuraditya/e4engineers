<?php

namespace App\Services\Notifications;

use App\Enums\NotificationChannel;
use App\Enums\NotificationType;
use App\Jobs\SendTransactionalEmail;
use App\Models\EmailSetting;
use App\Models\NotificationLog;
use App\Models\NotificationPreference;
use App\Models\NotificationTemplate;
use Illuminate\Support\Str;

class NotificationManager
{
    public function dispatch(NotificationType $type, string $recipient, array $context, string $deduplicationKey, ?int $userId = null, ?string $referenceType = null, string|int|null $referenceId = null): NotificationLog
    {
        if ($existing = NotificationLog::where('deduplication_key', $deduplicationKey)->first()) {
            return $existing;
        }
        $settings = EmailSetting::current();
        $template = NotificationTemplate::where('type', $type)->where('channel', NotificationChannel::Email)->first();
        $status = (! $settings->is_enabled || ! $template?->is_enabled || ! $this->allowed($type, $userId)) ? 'skipped' : 'queued';
        $log = NotificationLog::create(['id' => (string) Str::uuid(), 'deduplication_key' => $deduplicationKey, 'type' => $type, 'channel' => NotificationChannel::Email, 'recipient' => $recipient, 'notifiable_type' => $userId ? 'user' : null, 'notifiable_id' => $userId, 'template_id' => $template?->id, 'status' => $status, 'reference_type' => $referenceType, 'reference_id' => $referenceId, 'context' => $context, 'queued_at' => $status === 'queued' ? now() : null]);
        if ($status === 'queued') {
            $job = new SendTransactionalEmail($log->id);
            if ($settings->queue_enabled) {
                dispatch($job)->afterCommit();
            } else {
                try {
                    dispatch_sync($job);
                } catch (\Throwable) {
                    $log->update(['status' => 'failed', 'failed_at' => now(), 'failure_code' => 'MAIL_TRANSPORT_FAILED', 'failure_message' => 'Email delivery failed.']);
                }
            }
        }

        return $log;
    }

    public function retry(NotificationLog $log): NotificationLog
    {
        if (! in_array($log->status, ['failed', 'skipped'], true)) {
            return $log;
        } $log->update(['status' => 'queued', 'queued_at' => now(), 'failed_at' => null]);
        dispatch(new SendTransactionalEmail($log->id));

        return $log->refresh();
    }

    private function allowed(NotificationType $type, ?int $userId): bool
    {
        if (! $userId) {
            return true;
        } $preference = NotificationPreference::firstOrCreate(['user_id' => $userId]);
        $mandatory = [NotificationType::AccountActivation, NotificationType::PasswordSetup, NotificationType::EmailVerification, NotificationType::PasswordReset];
        if (in_array($type, $mandatory, true)) {
            return true;
        }
        if (! $preference->email_enabled) {
            return false;
        } if (str_starts_with($type->value, 'payment_')) {
            return $preference->payment_updates;
        } if (in_array($type, [NotificationType::ShipmentCreated, NotificationType::AwbAssigned, NotificationType::PickupScheduled, NotificationType::ShipmentPickedUp, NotificationType::ShipmentInTransit, NotificationType::OutForDelivery, NotificationType::Delivered, NotificationType::DeliveryFailed, NotificationType::RtoInitiated, NotificationType::RtoDelivered], true)) {
            return $preference->shipping_updates;
        }

        return $preference->order_updates;
    }
}
