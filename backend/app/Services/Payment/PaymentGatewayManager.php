<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Models\PaymentProvider;
use Illuminate\Validation\ValidationException;

class PaymentGatewayManager
{
    public function __construct(private RazorpayGateway $razorpay, private PayUGateway $payu, private CashfreeGateway $cashfree) {}

    public function for(PaymentProvider|string $provider): PaymentGatewayInterface
    {
        $code = $provider instanceof PaymentProvider ? $provider->code : $provider;

        return match ($code) {
            'RAZORPAY' => $this->razorpay,'PAYU' => $this->payu,'CASHFREE' => $this->cashfree,default => throw ValidationException::withMessages(['provider' => ['Unsupported online payment provider.']])
        };
    }
}
