<?php

declare(strict_types=1);

namespace App\Account\TransactionCost;

use App\SharedKernel\Money;

class TransactionCostCalculator implements TransactionCostCalculatorInterface
{
    private const int PERCENTAGE_TRANSACTION_COST = 5;

    public function calculate(Money $amount): Money
    {
        $calculatedCost = (int) ceil($amount->value * self::PERCENTAGE_TRANSACTION_COST / 100);

        return new Money(
            $amount->currency,
            $calculatedCost
        );
    }
}
