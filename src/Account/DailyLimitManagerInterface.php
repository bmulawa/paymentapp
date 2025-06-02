<?php

declare(strict_types=1);

namespace App\Account;

use App\Account\Model\AccountId;
use App\Account\Model\DailyLimit;

interface DailyLimitManagerInterface
{
    public function provideDailyLimitForAccount(AccountId $accountId): DailyLimit;

    public function persistDailyLimit(DailyLimit $dailyLimit): void;
}
