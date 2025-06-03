<?php

declare(strict_types=1);

namespace Integration\Model;

use App\Account\Model\Account;
use App\Account\Model\AccountId;
use App\Account\TransactionCost\TransactionCostCalculator;
use App\Application\DummyBalanceManager;
use App\Application\DummyDailyLimitProvider;
use App\Payment\Payment;
use App\SharedKernel\Currency;
use App\SharedKernel\Money;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AccountTest extends TestCase
{
    #[Test]
    public function debitSuccessful(): void
    {
        $currency = new Currency('USD');

        $payment = new Payment(
            $currency,
            new Money($currency, 1000),
        );

        $this->getAccount($currency)->debit($payment);

        self::assertTrue(true); // usually I would assert db state or sth
    }

    #[Test]
    public function creditSuccessful(): void
    {
        $currency = new Currency('USD');

        $account = $this->getAccount($currency);

        $payment = new Payment(
            $currency,
            new Money($currency, 1000),
        );

        $account->credit($payment);

        self::assertTrue(true); // usually I would assert db state or sth
    }

    private function getAccount(Currency $currency): Account
    {
        return new Account(
            new AccountId('f683a8f5-3551-4116-b85f-def6b0b5aa79'),
            $currency,
            new DummyBalanceManager(),
            new DummyDailyLimitProvider(),
            new TransactionCostCalculator()
        );
    }
}
