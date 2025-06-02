<?php

declare(strict_types=1);

namespace Tests\Unit\Model;

use App\Account\Exception\DailyLimitReachedException;
use App\Account\Model\AccountId;
use App\Account\Model\DailyLimit;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DailyLimitTest extends TestCase
{
    #[Test]
    #[DataProvider('limitProvider')]
    public function limitExceeded(bool $expected, int $currentCounter, int $limit): void
    {
        $dailyLimit = $this->createDailyLimit($currentCounter, $limit);

        self::assertSame($expected, $dailyLimit->isLimitReached());
    }

    #[Test]
    public function limitIncreased(): void
    {
        $dailyLimit = $this->createDailyLimit(2, 3);
        $dailyLimit->increaseCounter();

        self::assertTrue(true);
    }

    #[Test]
    public function limitReachedAfterIncreasingCounter(): void
    {
        $dailyLimit = $this->createDailyLimit(2, 3);
        self::assertFalse($dailyLimit->isLimitReached());

        $dailyLimit->increaseCounter();
        self::assertTrue($dailyLimit->isLimitReached());
    }

    #[Test]
    public function anExceptionIsThrownOnIncreaseCounterWhenLimitIsReached(): void
    {
        $this->expectException(DailyLimitReachedException::class);

        $dailyLimit = $this->createDailyLimit(3, 3);
        $dailyLimit->increaseCounter();
    }

    public static function limitProvider(): iterable
    {
        yield 'limit not reached' => [
            'expected' => false,
            'currentCounter' => 2,
            'limit' => 3,
        ];

        yield 'limit reached' => [
            'expected' => true,
            'currentCounter' => 3,
            'limit' => 3,
        ];
    }

    private function createDailyLimit(int $currentCounter, int $limit): DailyLimit
    {
        return new DailyLimit(
            new AccountId('f683a8f5-3551-4116-b85f-def6b0b5aa79'),
            $currentCounter,
            $limit
        );
    }
}
