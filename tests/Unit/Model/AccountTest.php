<?php

declare(strict_types=1);

namespace Tests\Unit\Model;

use App\Account\BalanceManagerInterface;
use App\Account\DailyLimitManagerInterface;
use App\Account\Exception\CurrencyMismatchException;
use App\Account\Exception\DailyLimitReachedException;
use App\Account\Exception\InsufficientFundsException;
use App\Account\Exception\InvalidAmountException;
use App\Account\Model\Account;
use App\Account\Model\AccountId;
use App\Account\Model\DailyLimit;
use App\Account\TransactionCost\TransactionCostCalculatorInterface;
use App\Payment\Payment;
use App\SharedKernel\Currency;
use App\SharedKernel\Money;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AccountTest extends TestCase
{
    private const string VALID_CURRENCY_CODE = 'USD';
    private const string UUID = 'f683a8f5-3551-4116-b85f-def6b0b5aa79';
    private const int TRANSACTION_COST = 5;
    private const int CURRENT_BALANCE = 10000;

    private BalanceManagerInterface $balanceManager;
    private DailyLimitManagerInterface $dailyLimitManager;
    private TransactionCostCalculatorInterface $transactionCostCalculator;

    private AccountId $accountId;

    public function setUp(): void
    {
        parent::setUp();

        $this->balanceManager = $this->createMock(BalanceManagerInterface::class);
        $this->dailyLimitManager = $this->createMock(DailyLimitManagerInterface::class);
        $this->transactionCostCalculator = $this->createMock(TransactionCostCalculatorInterface::class);

        $this->accountId = new AccountId(self::UUID);
    }

    #[Test]
    public function debitSuccessful(): void
    {
        $dailyLimit = $this->setUpDailyLimitFromProvider();

        $this->calculateTransactionCost();
        $this->balanceManagerReturnsCurrentBalance();
        $this->dailyLimitWasIncreased($dailyLimit);
        $this->balanceWasPersistedWithProperAmount(self::CURRENT_BALANCE - 1000 - 5);
        $this->dailyLimitWasPersisted($dailyLimit);

        $payment = new Payment(
            new Currency(self::VALID_CURRENCY_CODE),
            new Money(new Currency(self::VALID_CURRENCY_CODE), 1000),
        );

        $this->createAccount()->debit($payment);
    }

    #[Test]
    public function debitFailedBecauseCurrencyDiffers(): void
    {
        $this->expectException(CurrencyMismatchException::class);
        $this->expectDebitFailed();

        $payment = new Payment(
            new Currency('DIFFERENT-CURRENCY'),
            new Money(new Currency('DIFFERENT-CURRENCY'), 1000),
        );

        $this->createAccount()->debit($payment);
    }

    #[Test]
    public function debitFailedBecauseInsufficientFunds(): void
    {
        $this->expectException(InsufficientFundsException::class);
        $this->expectDebitFailed();

        $this->calculateTransactionCost(0);
        $this->balanceManagerReturnsCurrentBalance();

        $payment = new Payment(
            new Currency(self::VALID_CURRENCY_CODE),
            new Money(new Currency(self::VALID_CURRENCY_CODE), self::CURRENT_BALANCE + 1),
        );

        $this->createAccount()->debit($payment);
    }

    #[Test]
    public function debitFailedBecauseDailyLimitReached(): void
    {
        $this->expectException(DailyLimitReachedException::class);
        $this->expectDebitFailed();

        $this->setUpDailyLimitFromProvider(true);

        $this->calculateTransactionCost();
        $this->balanceManagerReturnsCurrentBalance();

        $payment = new Payment(
            new Currency(self::VALID_CURRENCY_CODE),
            new Money(new Currency(self::VALID_CURRENCY_CODE), 1000),
        );
        $this->createAccount()->debit($payment);
    }

    #[Test]
    public function debitFailedBecauseInvalidAmountInCreditOperation(): void
    {
        $this->expectException(InvalidAmountException::class);
        $this->expectDebitFailed();

        $this->setUpDailyLimitFromProvider();

        $this->calculateTransactionCost();
        $this->balanceManagerReturnsCurrentBalance();

        $payment = new Payment(
            new Currency(self::VALID_CURRENCY_CODE),
            new Money(new Currency(self::VALID_CURRENCY_CODE), -1000),
        );
        $this->createAccount()->debit($payment);
    }

    #[Test]
    public function creditSuccessful(): void
    {
        $this->balanceManagerReturnsCurrentBalance();
        $this->balanceWasPersistedWithProperAmount(self::CURRENT_BALANCE + 1000);

        $payment = new Payment(
            new Currency(self::VALID_CURRENCY_CODE),
            new Money(new Currency(self::VALID_CURRENCY_CODE), 1000),
        );
        $this->createAccount()->credit($payment);
    }

    #[Test]
    public function creditFailedBecauseCurrencyDiffers(): void
    {
        $this->expectException(CurrencyMismatchException::class);

        $payment = new Payment(
            new Currency('DIFFERENT-CURRENCY'),
            new Money(new Currency('DIFFERENT-CURRENCY'), 1000),
        );

        $this->createAccount()->credit($payment);
    }

    #[Test]
    public function creditFailedBecauseInvalidAmountInCreditOperation(): void
    {
        $this->expectException(InvalidAmountException::class);

        $payment = new Payment(
            new Currency(self::VALID_CURRENCY_CODE),
            new Money(new Currency(self::VALID_CURRENCY_CODE), -1000),
        );
        $this->createAccount()->credit($payment);
    }

    private function createAccount(): Account
    {
        return new Account(
            $this->accountId,
            new Currency(self::VALID_CURRENCY_CODE),
            $this->balanceManager,
            $this->dailyLimitManager,
            $this->transactionCostCalculator,
        );
    }

    private function dailyLimitWasIncreased(MockObject $dailyLimit): void
    {
        $dailyLimit
            ->expects($this->once())
            ->method('increaseCounter');
    }

    private function calculateTransactionCost(int $transactionCost = self::TRANSACTION_COST): void
    {
        $this->transactionCostCalculator->expects($this->once())
            ->method('calculate')
            ->willReturn(new Money(new Currency(self::VALID_CURRENCY_CODE), $transactionCost));
    }

    private function setUpDailyLimitFromProvider(bool $limitReached = false): DailyLimit|MockObject
    {
        $dailyLimit = $this->createMock(DailyLimit::class);
        $dailyLimit->expects($this->once())
            ->method('isLimitReached')
            ->willReturn($limitReached);

        $this->dailyLimitManager->expects($this->once())
            ->method('provideDailyLimitForAccount')
            ->willReturn($dailyLimit);

        return $dailyLimit;
    }

    private function balanceWasPersistedWithProperAmount(int $expectedAmount): void
    {
        $this->balanceManager->expects($this->once())
            ->method('persistBalanceForAccount')
            ->willReturnCallback(
                function (AccountId $accountId, Money $balance) use ($expectedAmount): void {
                    self::assertSame($expectedAmount, $balance->value);
                }
            );
    }

    private function dailyLimitWasPersisted(MockObject|DailyLimit $dailyLimit): void
    {
        $this->dailyLimitManager->expects($this->once())
            ->method('persistDailyLimit')
            ->with($dailyLimit);
    }

    private function balanceManagerReturnsCurrentBalance(): void
    {
        $this->balanceManager->expects($this->once())
            ->method('getBalanceForAccount')
            ->willReturn(new Money(new Currency(self::VALID_CURRENCY_CODE), self::CURRENT_BALANCE));
    }

    private function expectDebitFailed(): void
    {
        $this->balanceManager->expects($this->never())
            ->method('persistBalanceForAccount');

        $this->dailyLimitManager->expects($this->never())
            ->method('persistDailyLimit');
    }
}
