<?php

namespace App\Enums;

enum NotificationChannel: string
{
    case Email = 'email';
    case Sms = 'sms';
    case Whatsapp = 'whatsapp';
    case Push = 'push';
}
