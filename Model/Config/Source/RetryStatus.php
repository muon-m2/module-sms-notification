<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Muon\SMSNotification\Model\RetryQueue;

/**
 * Provides the set of retry-queue statuses for grid filtering and display.
 */
class RetryStatus implements OptionSourceInterface
{
    /**
     * Get options.
     *
     * @return array<int, array<string, string>>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => RetryQueue::STATUS_PENDING, 'label' => (string)__('Pending')],
            ['value' => RetryQueue::STATUS_PROCESSING, 'label' => (string)__('Processing')],
            ['value' => RetryQueue::STATUS_FAILED, 'label' => (string)__('Failed')],
            ['value' => RetryQueue::STATUS_DEAD, 'label' => (string)__('Dead-letter')],
        ];
    }
}
