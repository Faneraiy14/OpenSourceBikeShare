<?php

declare(strict_types=1);

namespace BikeShare\Test\Unit\SmsConnector;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use BikeShare\SmsConnector\DisabledConnector;
use BikeShare\SmsConnector\EuroSmsConnector;
use BikeShare\SmsConnector\SmsConnectorFactory;
use BikeShare\SmsConnector\TextmagicSmsConnector;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;

class SmsConnectorFactoryTest extends TestCase
{
    /**
     * @param string $connectorName
     * @param bool $debugMode
     * @param string $expectedInstance
     * @param string|null $expectedExceptionMessage
     */
    #[DataProvider('getConnectorDataProvider')]
    #[AllowMockObjectsWithoutExpectations]
    public function testGetConnector(
        $connectorName,
        $debugMode,
        $expectedInstance,
        $expectedExceptionMessage = null
    ) {
        // Always a mock (not a stub) - a mock without expects() set behaves
        // exactly like a stub, and this keeps $logger's static type
        // consistent instead of a Mock|Stub union PHPStan can't call
        // expects() on below.
        $logger = $this->createMock(LoggerInterface::class);
        $serviceLocatorMock = $this->createMock(ServiceLocator::class);

        if ($expectedExceptionMessage) {
            $serviceLocatorMock
                ->expects($this->once())
                ->method('get')
                ->with($connectorName)
                ->willThrowException(new \Exception($expectedExceptionMessage));
        } else {
            $serviceLocatorMock
                ->expects($this->once())
                ->method('get')
                ->with($connectorName)
                ->willReturn($this->createStub($expectedInstance));
        }

        $smsConnectorFactory = new SmsConnectorFactory($connectorName, $serviceLocatorMock, $logger);

        if ($expectedExceptionMessage) {
            $logger
                ->expects($this->once())
                ->method('error')
                ->with(
                    'Error creating SMS connector',
                    $this->callback(fn($context) => $context['connector'] === $connectorName
                        && $context['exception'] instanceof \Exception
                        && $context['exception']->getMessage() === $expectedExceptionMessage)
                );
        }

        $result = $smsConnectorFactory->getConnector();
        $this->assertInstanceOf($expectedInstance, $result);
    }

    public static function getConnectorDataProvider()
    {
        yield 'eurosms' => [
            'connectorName' => 'eurosms',
            'debugMode' => true,
            'expectedInstance' => EuroSmsConnector::class,
        ];
        yield 'textmagic' => [
            'connectorName' => 'textmagic.com',
            'debugMode' => true,
            'expectedInstance' => TextmagicSmsConnector::class,
        ];
        yield 'unknown' => [
            'connectorName' => 'unknown',
            'debugMode' => true,
            'expectedInstance' => DisabledConnector::class,
        ];

        yield 'throwException' => [
            'connectorName' => 'eurosms',
            'debugMode' => false,
            'expectedInstance' => DisabledConnector::class,
            'expectedExceptionMessage' => 'Invalid EuroSms configuration',
        ];
    }
}
