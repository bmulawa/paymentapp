<?php

namespace App\Account;

use App\Account\Model\AccountId;
use App\SharedKernel\Money;

interface BalanceManagerInterface
{
    public function getBalanceForAccount(AccountId $accountId): Money;

    public function persistBalanceForAccount(AccountId $accountId, Money $balance);
}
