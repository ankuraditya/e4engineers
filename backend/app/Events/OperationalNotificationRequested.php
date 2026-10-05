<?php

namespace App\Events;

use App\Enums\NotificationType;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OperationalNotificationRequested
{
    use Dispatchable, SerializesModels;

    public function __construct(public NotificationType $type, public string $recipient, public array $context, public string $key, public ?int $userId = null, public ?string $referenceType = null, public string|int|null $referenceId = null) {}
}
