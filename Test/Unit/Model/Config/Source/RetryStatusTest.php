<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Test\Unit\Model\Config\Source;

use Muon\SMSNotification\Model\Config\Source\RetryStatus;
use Muon\SMSNotification\Model\RetryQueue;
use PHPUnit\Framework\TestCase;

class RetryStatusTest extends TestCase
{
    public function testToOptionArrayExposesAllStatuses(): void
    {
        $options = (new RetryStatus())->toOptionArray();

        $values = array_column($options, 'value');

        $this->assertSame(
            [
                RetryQueue::STATUS_PENDING,
                RetryQueue::STATUS_PROCESSING,
                RetryQueue::STATUS_FAILED,
                RetryQueue::STATUS_DEAD,
            ],
            $values
        );
        $this->assertCount(4, $options);
        foreach ($options as $option) {
            $this->assertArrayHasKey('label', $option);
        }
    }
}
