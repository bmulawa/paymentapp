<?php

declare(strict_types=1);

namespace App\Application;

use App\Account\DailyLimitManagerInterface;
use App\Account\Model\AccountId;
use App\Account\Model\DailyLimit;

class DummyDailyLimitProvider implements DailyLimitManagerInterface
{
    private const int DUMMY_LIMIT = 3;

    public function provideDailyLimitForAccount(AccountId $accountId): DailyLimit
    {
        return new DailyLimit(
            $accountId,
            0,
            self::DUMMY_LIMIT
        );
    }

    public function persistDailyLimit(DailyLimit $dailyLimit): void
    {
    }
}
