<?php

namespace App\Console\Commands;

use App\Models\PaymentAttempt;
use App\Services\Payment\PaymentService;
use Illuminate\Console\Command;

class ExpirePaymentAttempts extends Command
{
    protected $signature = 'payments:expire-attempts';

    protected $description = 'Expire pending payment attempts and release inventory reservations';

    public function handle(PaymentService $payments): int
    {
        PaymentAttempt::whereIn('status', ['initiating', 'pending', 'pending_review'])->where('expires_at', '<', now())->eachById(function ($attempt) use ($payments) {
            $payments->fail($attempt, ['code' => 'ATTEMPT_EXPIRED']);
            $attempt->update(['status' => 'expired']);
        });

        return self::SUCCESS;
    }
}
