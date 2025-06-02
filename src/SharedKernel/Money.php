<?php

declare(strict_types=1);

namespace App\SharedKernel;

readonly class Money
{
    public function __construct(
        public Currency $currency,
        public int $value,
    )
    {
    }
}
