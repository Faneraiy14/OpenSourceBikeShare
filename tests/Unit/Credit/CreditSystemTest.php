<?php

declare(strict_types=1);

namespace BikeShare\Test\Unit\Credit;

use PHPUnit\Framework\Attributes\DataProvider;
use BikeShare\Credit\CreditSystem;
use BikeShare\Db\DbInterface;
use BikeShare\Db\DbResultInterface;
use BikeShare\Repository\HistoryRepository;
use PHPUnit\Framework\TestCase;

class CreditSystemTest extends TestCase
{
    #[DataProvider('constructorDataProvider')]
    public function testConstructor(
        $isEnabled,
        $creditCurrency,
        $minRequiredCredit,
        $rentalFee,
        $priceCycle,
        $longRentalFee,
        $limitIncreaseFee,
        $violationFee,
        $expectedMinRequiredCredit,
        $expectedException = null
    ) {
        if (!is_null($expectedException)) {
            $this->expectException($expectedException);
        }

        $creditSystem = new CreditSystem(
            $isEnabled,
            $creditCurrency,
            $minRequiredCredit,
            $rentalFee,
            $priceCycle,
            $longRentalFee,
            $limitIncreaseFee,
            $violationFee,
            $this->createStub(DbInterface::class),
            $this->createStub(HistoryRepository::class)
        );
        $this->assertEquals($isEnabled, $creditSystem->isEnabled());
        $this->assertEquals($creditCurrency, $creditSystem->getCreditCurrency());
        $this->assertEquals($expectedMinRequiredCredit, $creditSystem->getMinRequiredCredit());
        $this->assertEquals($rentalFee, $creditSystem->getRentalFee());
        $this->assertEquals($priceCycle, $creditSystem->getPriceCycle());
        $this->assertEquals($longRentalFee, $creditSystem->getLongRentalFee());
        $this->assertEquals($limitIncreaseFee, $creditSystem->getLimitIncreaseFee());
        $this->assertEquals($violationFee, $creditSystem->getViolationFee());
    }

    public static function constructorDataProvider()
    {
        $default = [
            'isEnabled' => true,
            'creditCurrency' => '$',
            'minRequiredCredit' => 12,
            'rentalFee' => 3,
            'priceCycle' => 1,
            'longRentalFee' => 6,
            'limitIncreaseFee' => 11,
            'violationFee' => 6,
            'expectedMinRequiredCredit' => 21,
        ];
        yield 'enabled configuration' => $default;
        yield 'disabled configuration' => array_merge(
            $default,
            [
                'isEnabled' => false,
                'expectedException' => \RuntimeException::class,
            ]
        );
        yield 'negative minRequiredCredit' => array_merge(
            $default,
            [
                'minRequiredCredit' => -1,
                'expectedException' => \InvalidArgumentException::class,
            ]
        );
        yield 'negative rentalFee' => array_merge(
            $default,
            [
                'rentalFee' => -1,
                'expectedException' => \InvalidArgumentException::class,
            ]
        );
        yield 'negative priceCycle' => array_merge(
            $default,
            [
                'priceCycle' => -1,
                'expectedException' => \InvalidArgumentException::class,
            ]
        );
        yield 'negative longRentalFee' => array_merge(
            $default,
            [
                'longRentalFee' => -1,
                'expectedException' => \InvalidArgumentException::class,
            ]
        );
        yield 'negative limitIncreaseFee' => array_merge(
            $default,
            [
                'limitIncreaseFee' => -1,
                'expectedException' => \InvalidArgumentException::class,
            ]
        );
        yield 'negative violationFee' => array_merge(
            $default,
            [
                'violationFee' => -1,
                'expectedException' => \InvalidArgumentException::class,
            ]
        );
    }

    public function testGetUserCredit()
    {
        $userId = 1;
        $dbResult = $this->createMock(DbResultInterface::class);
        $dbResult->expects($this->once())
            ->method('fetchAssoc')
            ->willReturn(['credit' => 5]);
        $dbResult->expects($this->once())
            ->method('rowCount')
            ->willReturn(1);

        $db = $this->createMock(DbInterface::class);
        $db->expects($this->once())
            ->method('query')
            ->with('SELECT credit FROM credit WHERE userId = :userId', ['userId' => $userId])
            ->willReturn($dbResult);

        $creditSystem = new CreditSystem(
            true, //isEnabled
            '€', //creditCurrency
            9, //minRequiredCredit
            2, //rentalFee
            0, //priceCycle
            5, //longRentalFee
            10, //limitIncreaseFee
            5, //violationFee
            $db,
            $this->createStub(HistoryRepository::class)
        );

        $this->assertEquals(5, $creditSystem->getUserCredit($userId));
    }

    public function testGetUserCreditNotFoundUser()
    {
        $userId = 1;

        $dbResult = $this->createMock(DbResultInterface::class);
        $dbResult->expects($this->once())
            ->method('rowCount')
            ->willReturn(0);

        $db = $this->createMock(DbInterface::class);
        $db->expects($this->once())
            ->method('query')
            ->with('SELECT credit FROM credit WHERE userId = :userId', ['userId' => $userId])
            ->willReturn($dbResult);

        $creditSystem = new CreditSystem(
            true, //isEnabled
            '€', //creditCurrency
            9, //minRequiredCredit
            2, //rentalFee
            0, //priceCycle
            5, //longRentalFee
            10, //limitIncreaseFee
            5, //violationFee
            $db,
            $this->createStub(HistoryRepository::class)
        );

        $this->assertEquals(0, $creditSystem->getUserCredit($userId));
    }

    public static function creditHistoryReasonProvider(): iterable
    {
        yield 'known reason' => ['coupon_redemption', 'coupon_redemption'];
        yield 'unrecognized reason maps to unknown' => ['some_legacy_reason_no_longer_in_the_enum', 'unknown'];
    }

    #[DataProvider('creditHistoryReasonProvider')]
    public function testGetUserCreditHistoryMapsReasonType(string $storedReason, string $expectedType): void
    {
        $userId = 1;
        $historyRepository = $this->createMock(HistoryRepository::class);
        $historyRepository->expects($this->once())
            ->method('findCreditHistoryByUser')
            ->with($userId, 1000)
            ->willReturn([
                [
                    'id' => 1,
                    'time' => '2026-01-01 12:00:00',
                    'action' => 5,
                    'parameter' => json_encode([
                        'amount' => 3.5,
                        'balance' => 10.0,
                        'reason' => $storedReason,
                    ], JSON_THROW_ON_ERROR),
                ],
            ]);

        $creditSystem = new CreditSystem(
            true,
            '€',
            9,
            2,
            0,
            5,
            10,
            5,
            $this->createStub(DbInterface::class),
            $historyRepository
        );

        $result = $creditSystem->getUserCreditHistory($userId);

        $this->assertCount(1, $result);
        $this->assertEquals($expectedType, $result[0]['type']);
        $this->assertEquals(3.5, $result[0]['amount']);
        $this->assertEquals(10.0, $result[0]['balance']);
    }
}
