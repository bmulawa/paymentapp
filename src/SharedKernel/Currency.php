<?php

declare(strict_types=1);

namespace App\SharedKernel;

class Currency
{
    public function __construct(
        private string $code,
    )
    {
    }

    public function equals(Currency $currency): bool
    {
        return $this->code === $currency->code;
    }
}
