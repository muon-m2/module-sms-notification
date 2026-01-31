<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Test\Integration\Model;

use Magento\TestFramework\Helper\Bootstrap;
use Muon\SMSNotification\Model\SmsTransportPool;
use Muon\SMSNotification\Model\SmsTransport\Twilio;
use Muon\SMSNotification\Model\SmsTransport\Logger;
use PHPUnit\Framework\TestCase;

class SmsTransportPoolTest extends TestCase
{
    private $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    public function testGetTransportReturnsCorrectInstances(): void
    {
        /** @var SmsTransportPool $pool */
        $pool = $this->objectManager->get(SmsTransportPool::class);

        $twilio = $pool->getTransport('twilio');
        $this->assertInstanceOf(Twilio::class, $twilio);

        $logger = $pool->getTransport('logger');
        $this->assertInstanceOf(Logger::class, $logger);
    }

    public function testGetTransportThrowsExceptionForUnknownTransport(): void
    {
        /** @var SmsTransportPool $pool */
        $pool = $this->objectManager->get(SmsTransportPool::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('SMS transport with code "unknown" not found.');

        $pool->getTransport('unknown');
    }
}
