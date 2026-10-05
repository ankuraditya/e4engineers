<?php

namespace App\Enums;

enum DigitalResourceAccessType: string
{
    case Free = 'free';
    case LoginRequired = 'login_required';
    case Paid = 'paid';
}
