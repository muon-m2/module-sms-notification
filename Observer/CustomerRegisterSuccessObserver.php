<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Observer;

use Muon\SMSNotification\Model\Config;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Muon\SMSNotification\Api\NotifierInterface;
use Muon\SMSNotification\Api\MessageBuilderInterface;
use Psr\Log\LoggerInterface;

/**
 * Observes the `customer_register_success` event.
 */
class CustomerRegisterSuccessObserver implements ObserverInterface
{
    /**
     * @param NotifierInterface $notifier
     * @param Config $config
     * @param MessageBuilderInterface $messageBuilder
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly NotifierInterface $notifier,
        private readonly Config $config,
        private readonly MessageBuilderInterface $messageBuilder,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * SMS notification is best-effort: any failure here is logged and swallowed so it can
     * never interrupt customer registration.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        try {
            /** @var \Magento\Customer\Api\Data\CustomerInterface|null $customer */
            $customer = $observer->getEvent()->getData('customer');

            if ($customer === null || !$this->config->isCustomerRegisterEnabled((int)$customer->getStoreId())) {
                return;
            }

            $storeId = (int)$customer->getStoreId();
            $this->notifier->sendSMS(
                $this->config->getSendToPhone($storeId),
                $this->messageBuilder->getMessage($customer),
                $storeId
            );
        } catch (\Throwable $e) {
            $this->logger->error('SMS customer registration notification failed: ' . $e->getMessage());
        }
    }
}
