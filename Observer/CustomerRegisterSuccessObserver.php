<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Observer;

use Muon\SMSNotification\Model\Config;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Muon\SMSNotification\Api\NotifierInterface;
use Muon\SMSNotification\Api\MessageBuilderInterface;

/**
 * Observes the `customer_register_success` event.
 */
class CustomerRegisterSuccessObserver implements ObserverInterface
{
    /**
     * @param NotifierInterface $notifier
     * @param Config $config
     * @param MessageBuilderInterface $messageBuilder
     */
    public function __construct(
        private readonly NotifierInterface $notifier,
        private readonly Config $config,
        private readonly MessageBuilderInterface $messageBuilder
    ) {
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        /** @var \Magento\Customer\Api\Data\CustomerInterface $customer */
        $customer = $observer->getEvent()->getData('customer');

        if (!$this->config->isCustomerRegisterEnabled((int)$customer->getStoreId())) {
            return;
        }

        $phone = $this->config->getSendToPhone($customer->getStoreId());
        $this->notifier->sendSMS(
            $phone,
            $this->messageBuilder->getMessage($customer),
            (int)$customer->getStoreId()
        );
    }
}
