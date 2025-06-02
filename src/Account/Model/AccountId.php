<?php

declare(strict_types=1);

namespace App\Account\Model;

readonly class AccountId
{
    public function __construct(
        public string $uuid,
    )
    {
    }
}
