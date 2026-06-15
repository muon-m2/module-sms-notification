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
 * Observes `sales_order_shipment_save_after` and notifies the configured recipient that the
 * order has shipped.
 */
class SalesOrderShipmentSaveAfterObserver implements ObserverInterface
{
    /**
     * @param NotifierInterface       $notifier
     * @param Config                  $config
     * @param MessageBuilderInterface $messageBuilder
     * @param RecipientResolver       $recipientResolver
     * @param LoggerInterface         $logger
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
     * Best-effort: any failure is logged and swallowed.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        try {
            /** @var \Magento\Sales\Model\Order\Shipment|null $shipment */
            $shipment = $observer->getEvent()->getData('shipment');
            if ($shipment === null) {
                return;
            }

            $order = $shipment->getOrder();
            if ($order === null) {
                return;
            }

            $storeId = (int)$order->getStoreId();
            if (!$this->config->isShipmentEnabled($storeId)) {
                return;
            }

            $this->notifier->sendSMS(
                $this->recipientResolver->resolveForOrder($order),
                $this->messageBuilder->getMessage($shipment),
                $storeId
            );
        } catch (\Throwable $e) {
            $this->logger->error('SMS shipment notification failed: ' . $e->getMessage());
        }
    }
}
