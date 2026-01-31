<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Model;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Muon\SMSNotification\Api\MessageBuilderInterface;

class CustomerMessageBuilder implements MessageBuilderInterface
{
    /**
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private readonly Config $config,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getMessage(mixed $data): string
    {
        if (!$data instanceof CustomerInterface) {
            return 'Customer data is not valid';
        }

        $storeId = (int)$data->getStoreId();
        $template = $this->config->getCustomerRegisterTemplate($storeId);
        $store = $this->storeManager->getStore($storeId);

        $variables = [
            '{{customer_name}}' => $data->getFirstname() . ' ' . $data->getLastname(),
            '{{customer_email}}' => $data->getEmail(),
            '{{store_name}}' => $store->getName(),
        ];

        return str_replace(array_keys($variables), array_values($variables), $template);
    }
}
