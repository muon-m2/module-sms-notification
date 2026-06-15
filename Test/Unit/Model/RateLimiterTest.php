<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Test\Unit\Model;

use Magento\Framework\App\CacheInterface;
use Muon\SMSNotification\Model\Config;
use Muon\SMSNotification\Model\RateLimiter;
use PHPUnit\Framework\TestCase;

class RateLimiterTest extends TestCase
{
    private $cacheMock;
    private $configMock;
    private $limiter;

    protected function setUp(): void
    {
        $this->cacheMock = $this->createMock(CacheInterface::class);
        $this->configMock = $this->createMock(Config::class);
        $this->limiter = new RateLimiter($this->cacheMock, $this->configMock);
    }

    public function testUnlimitedAlwaysAcquires(): void
    {
        $this->configMock->method('getRateLimitPerMinute')->willReturn(0);
        $this->cacheMock->expects($this->never())->method('load');

        $this->assertTrue($this->limiter->tryAcquire(1));
    }

    public function testAcquiresWhenUnderLimit(): void
    {
        $this->configMock->method('getRateLimitPerMinute')->willReturn(5);
        $this->cacheMock->method('load')->willReturn('2');
        $this->cacheMock->expects($this->once())->method('save')->with('3', $this->anything(), $this->anything(), 120);

        $this->assertTrue($this->limiter->tryAcquire(1));
    }

    public function testRejectsWhenAtLimit(): void
    {
        $this->configMock->method('getRateLimitPerMinute')->willReturn(5);
        $this->cacheMock->method('load')->willReturn('5');
        $this->cacheMock->expects($this->never())->method('save');

        $this->assertFalse($this->limiter->tryAcquire(1));
    }
}
