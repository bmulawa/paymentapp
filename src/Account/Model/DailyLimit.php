<?php

declare(strict_types=1);

namespace App\Account\Model;

use App\Account\Exception\DailyLimitReachedException;

class DailyLimit
{
    public function __construct(
        private AccountId $accountId,
        private int $counter,
        private int $limit,
    )
    {
    }

    public function isLimitReached(): bool
    {
        return $this->counter >= $this->limit;
    }

    public function increaseCounter(): void
    {
        if ($this->isLimitReached()) {
            throw new DailyLimitReachedException('Daily limit reached');
        }

        ++$this->counter;
    }
}
