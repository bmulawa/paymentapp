<?php

declare(strict_types=1);

namespace Tests\Unit\TransactionCost;

use App\Account\TransactionCost\TransactionCostCalculator;
use App\SharedKernel\Currency;
use App\SharedKernel\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TransactionCostCalculatorTest extends TestCase
{
    #[Test]
    #[DataProvider('transactionValueProvider')]
    public function calculateTransactionCost(int $expected, int $transactionValue): void
    {
        $calculator = new TransactionCostCalculator();

        $actual = $calculator->calculate(new Money(new Currency('PLN'), $transactionValue))->value;
        self::assertSame($expected, $actual);
    }

    public static function transactionValueProvider(): iterable
    {
        yield [5, 1000];
        yield [1, 1];
    }
}
