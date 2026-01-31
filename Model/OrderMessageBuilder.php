<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Model;

use Magento\Sales\Api\Data\OrderInterface;
use Muon\SMSNotification\Api\MessageBuilderInterface;

class OrderMessageBuilder implements MessageBuilderInterface
{
    /**
     * @param Config $config
     */
    public function __construct(
        private readonly Config $config
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getMessage(mixed $data): string
    {
        if (!$data instanceof OrderInterface) {
            return 'Order data is not valid';
        }

        $storeId = (int)$data->getStoreId();
        $template = $this->config->getOrderTemplate($storeId);

        $itemsSummary = [];
        foreach ($data->getAllVisibleItems() as $item) {
            $itemsSummary[] = sprintf('%s x %s', (int)$item->getQtyOrdered(), $item->getSku());
        }

        $variables = [
            '{{increment_id}}' => $data->getIncrementId(),
            '{{shipping_description}}' => $data->getShippingDescription(),
            '{{customer_name}}' => $data->getCustomerFirstname() . ' ' . $data->getCustomerLastname(),
            '{{order_total}}' => $data->getGrandTotal(),
            '{{items}}' => implode(', ', $itemsSummary)
        ];

        return str_replace(array_keys($variables), array_values($variables), $template);
    }
}
