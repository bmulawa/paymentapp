<?php

declare(strict_types=1);

namespace App\Application;

use App\Account\BalanceManagerInterface;
use App\Account\Model\AccountId;
use App\SharedKernel\Currency;
use App\SharedKernel\Money;

class DummyBalanceManager implements BalanceManagerInterface
{
    public function getBalanceForAccount(AccountId $accountId): Money
    {
        return new Money(
            new Currency('EUR'),
            1531 * 10000
        );
    }

    public function persistBalanceForAccount(AccountId $accountId, Money $balance): void
    {

    }
}
