<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Test\Unit\Model;

use Muon\SMSNotification\Model\Config;
use Muon\SMSNotification\Model\MessageFormatter;
use PHPUnit\Framework\TestCase;

class MessageFormatterTest extends TestCase
{
    private $configMock;
    private $formatter;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->formatter = new MessageFormatter($this->configMock);
    }

    public function testNoTruncationWhenUnlimited(): void
    {
        $this->configMock->method('getMaxLength')->willReturn(0);
        $this->assertSame('hello world', $this->formatter->format('hello world', 1));
    }

    public function testNoTruncationWhenUnderMax(): void
    {
        $this->configMock->method('getMaxLength')->willReturn(50);
        $this->assertSame('hello', $this->formatter->format('hello', 1));
    }

    public function testTruncatesWithEllipsis(): void
    {
        $this->configMock->method('getMaxLength')->willReturn(10);
        // 9 chars + ellipsis = 10.
        $this->assertSame('This is a…', $this->formatter->format('This is a very long message', 1));
    }

    public function testSegmentCountSingleGsm(): void
    {
        $this->assertSame(1, $this->formatter->segmentCount(str_repeat('a', 160)));
    }

    public function testSegmentCountMultiGsm(): void
    {
        // 161 chars -> ceil(161 / 153) = 2.
        $this->assertSame(2, $this->formatter->segmentCount(str_repeat('a', 161)));
    }

    public function testSegmentCountUnicodeShrinksSingleLimit(): void
    {
        // 71 unicode chars -> ceil(71 / 67) = 2.
        $this->assertSame(2, $this->formatter->segmentCount(str_repeat('é', 71)));
    }

    public function testEmptyTextIsZeroSegments(): void
    {
        $this->assertSame(0, $this->formatter->segmentCount(''));
    }
}
