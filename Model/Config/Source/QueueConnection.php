<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Provides options for Queue Connection.
 */
class QueueConnection implements OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'db', 'label' => __('Database (Default/Fallback)')],
            ['value' => 'amqp', 'label' => __('RabbitMQ (AMQP)')]
        ];
    }
}
