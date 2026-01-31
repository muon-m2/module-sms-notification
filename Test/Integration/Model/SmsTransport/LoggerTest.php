<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Test\Integration\Model\SmsTransport;

use Magento\TestFramework\Helper\Bootstrap;
use Muon\SMSNotification\Model\SmsTransport\Logger as LoggerTransport;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    private $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    public function testSendLogsMessage(): void
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())
            ->method('info')
            ->with(
                'SMS notification (Logged Only)',
                [
                    'phone' => '+1234567890',
                    'message' => 'Test message',
                    'store_id' => 1
                ]
            );

        /** @var LoggerTransport $loggerTransport */
        $loggerTransport = $this->objectManager->create(LoggerTransport::class, [
            'logger' => $loggerMock
        ]);

        $loggerTransport->send('+1234567890', 'Test message', 1);
    }
}
