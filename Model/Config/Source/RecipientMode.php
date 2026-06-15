<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Muon\SMSNotification\Model\RecipientResolver;

/**
 * Recipient options for order/shipment notifications.
 */
class RecipientMode implements OptionSourceInterface
{
    /**
     * @return array<int, array<string, string>>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => RecipientResolver::MODE_ADMIN, 'label' => (string)__('Admin (single number)')],
            ['value' => RecipientResolver::MODE_CUSTOMER, 'label' => (string)__('Customer (order phone)')],
        ];
    }
}
