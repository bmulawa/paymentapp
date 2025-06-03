<?php

declare(strict_types=1);

namespace App\Account\Model;

use App\Account\BalanceManagerInterface;
use App\Account\DailyLimitManagerInterface;
use App\Account\Exception\CurrencyMismatchException;
use App\Account\Exception\DailyLimitReachedException;
use App\Account\Exception\InsufficientFundsException;
use App\Account\Exception\InvalidAmountException;
use App\Account\TransactionCost\TransactionCostCalculatorInterface;
use App\Payment\Payment;
use App\SharedKernel\Currency;
use App\SharedKernel\Money;

class Account
{
    public function __construct(
        private AccountId $accountId,
        private Currency $currency,
        private BalanceManagerInterface $balanceManager,
        private DailyLimitManagerInterface $dailyLimitManager,
        private TransactionCostCalculatorInterface $costCalculator
    )
    {

    }

    public function debit(Payment $payment): void
    {
        if (!$payment->currency->equals($this->currency)) {
            throw new CurrencyMismatchException('Currency mismatch in debit operation');
        }

        $finalAmount = new Money(
            $this->currency,
            $payment->amount->value + $this->costCalculator->calculate($payment->amount)->value
        );

        $balance = $this->balanceManager->getBalanceForAccount($this->accountId);
        if ($balance->value < $finalAmount->value) {
            throw new InsufficientFundsException('Insufficient funds');
        }

        $dailyLimit = $this->dailyLimitManager->provideDailyLimitForAccount($this->accountId);
        
        if ($dailyLimit->isLimitReached()) {
            throw new DailyLimitReachedException('Daily limit reached');
        }
        
        if ($finalAmount->value <= 0) {
            throw new InvalidAmountException('Invalid amount in credit operation');
        }

        $balance = new Money($this->currency, $balance->value - $finalAmount->value);
        $dailyLimit->increaseCounter();
        
        try {
            // begin transaction
            $this->balanceManager->persistBalanceForAccount($this->accountId, $balance);
            $this->dailyLimitManager->persistDailyLimit($dailyLimit);
        } catch (\Throwable) {
            // error handling, rollback transaction
        }
    }

    public function credit(Payment $payment): void
    {
        if (!$payment->amount->currency->equals($this->currency)) {
            throw new CurrencyMismatchException('Currency mismatch in credit operation');
        }

        if ($payment->amount->value <= 0) {
            throw new InvalidAmountException('Invalid amount in credit operation');
        }

        $balance = $this->balanceManager->getBalanceForAccount($this->accountId);
        $updatedBalance = new Money($this->currency, $balance->value + $payment->amount->value);

        $this->balanceManager->persistBalanceForAccount($this->accountId, $updatedBalance);
    }
}
