<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Muon\SMSNotification\Model\SmsTransportPool;
use Muon\SMSNotification\Api\SmsTransportInterface;

class SmsTransportPoolTest extends TestCase
{
    public function testGetTransport(): void
    {
        $transportMock = $this->createMock(SmsTransportInterface::class);
        $pool = new SmsTransportPool(['twilio' => $transportMock]);

        $this->assertSame($transportMock, $pool->getTransport('twilio'));
    }

    public function testGetTransportNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('SMS transport with code "invalid" not found.');

        $pool = new SmsTransportPool([]);
        $pool->getTransport('invalid');
    }
}
