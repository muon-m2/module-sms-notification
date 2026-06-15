<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Observer;

use Muon\SMSNotification\Model\Config;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Muon\SMSNotification\Api\NotifierInterface;
use Muon\SMSNotification\Api\MessageBuilderInterface;
use Muon\SMSNotification\Model\RecipientResolver;
use Psr\Log\LoggerInterface;

/**
 * Observes the `sales_order_place_after` event.
 */
class SalesOrderPlaceAfterObserver implements ObserverInterface
{
    /**
     * Constructor method.
     *
     * @param NotifierInterface       $notifier          The notifier instance for sending notifications.
     * @param Config                  $config            The configuration settings for the service.
     * @param MessageBuilderInterface $messageBuilder    The message builder instance for constructing messages.
     * @param RecipientResolver       $recipientResolver Resolves the admin/customer recipient.
     * @param LoggerInterface         $logger            Logger for swallowed notification failures.
     *
     * @return void
     */
    public function __construct(
        private readonly NotifierInterface $notifier,
        private readonly Config $config,
        private readonly MessageBuilderInterface $messageBuilder,
        private readonly RecipientResolver $recipientResolver,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Observer for sales_order_place_after.
     *
     * SMS notification is best-effort: any failure here is logged and swallowed so it can
     * never interrupt or roll back order placement.
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer): void
    {
        try {
            /** @var \Magento\Sales\Api\Data\OrderInterface|null $order */
            $order = $observer->getEvent()->getData('order');
            if ($order === null || !$this->config->isOrderEnabled((int)$order->getStoreId())) {
                return;
            }
            $storeId = (int)$order->getStoreId();
            $this->notifier->sendSMS(
                $this->recipientResolver->resolveForOrder($order),
                $this->messageBuilder->getMessage($order),
                $storeId
            );
        } catch (\Throwable $e) {
            $this->logger->error('SMS order notification failed: ' . $e->getMessage());
        }
    }
}
