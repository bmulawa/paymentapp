<?php

namespace App\Account\TransactionCost;

use App\SharedKernel\Money;

interface TransactionCostCalculatorInterface
{
    public function calculate(Money $amount): Money;
}
