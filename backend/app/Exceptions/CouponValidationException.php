<?php

namespace App\Exceptions;

use RuntimeException;

class CouponValidationException extends RuntimeException
{
    public function __construct(public readonly string $reasonCode, string $message)
    {
        parent::__construct($message);
    }
}
