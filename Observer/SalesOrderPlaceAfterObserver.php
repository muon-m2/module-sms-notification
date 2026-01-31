<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Observer;

use Muon\SMSNotification\Model\Config;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Muon\SMSNotification\Api\NotifierInterface;
use Muon\SMSNotification\Api\MessageBuilderInterface;

/**
 * Observes the `sales_order_place_after` event.
 */
class SalesOrderPlaceAfterObserver implements ObserverInterface
{
    /**
     * Constructor method.
     *
     * @param NotifierInterface       $notifier       The notifier instance for sending notifications.
     * @param Config                  $config         The configuration settings for the service.
     * @param MessageBuilderInterface $messageBuilder The message builder instance for constructing messages.
     *
     * @return void
     */
    public function __construct(
        private readonly NotifierInterface $notifier,
        private readonly Config $config,
        private readonly MessageBuilderInterface $messageBuilder
    ) {
    }

    /**
     * Observer for sales_order_place_after.
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer): void
    {
        /** @var \Magento\Sales\Api\Data\OrderInterface $order */
        $order = $observer->getEvent()->getData('order');
        if (!$this->config->isOrderEnabled((int)$order->getStoreId())) {
            return;
        }
        $phone = $this->config->getSendToPhone($order->getStoreId());
        $this->notifier->sendSMS($phone, $this->messageBuilder->getMessage($order), (int)$order->getStoreId());
    }
}
