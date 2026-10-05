<?php

namespace App\Events;

use App\Models\PaymentAttempt;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentSucceeded
{
    use Dispatchable,SerializesModels;

    public function __construct(public readonly PaymentAttempt $attempt) {}
}
