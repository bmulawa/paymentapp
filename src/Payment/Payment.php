<?php

declare(strict_types=1);

namespace App\Payment;

use App\SharedKernel\Currency;
use App\SharedKernel\Money;

readonly class Payment
{
    public function __construct(
        public Currency $currency,
        public Money $amount,
    )
    {
    }

}
