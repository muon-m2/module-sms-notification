<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Model;

use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Muon\SMSNotification\Api\MessageBuilderInterface;

/**
 * Builds the SMS body for a shipment notification.
 */
class ShipmentMessageBuilder implements MessageBuilderInterface
{
    /**
     * @param Config                    $config
     * @param OrderRepositoryInterface  $orderRepository
     * @param StoreManagerInterface     $storeManager
     */
    public function __construct(
        private readonly Config $config,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getMessage(mixed $data): string
    {
        if (!$data instanceof ShipmentInterface) {
            return 'Shipment data is not valid';
        }

        $storeId = (int)$data->getStoreId();
        $template = $this->config->getShipmentTemplate($storeId);

        $incrementId = '';
        if ($data->getOrderId()) {
            $incrementId = (string)$this->orderRepository->get((int)$data->getOrderId())->getIncrementId();
        }

        $tracks = [];
        foreach ((array)$data->getTracks() as $track) {
            $number = $track->getTrackNumber();
            if ($number) {
                $tracks[] = $number;
            }
        }

        $variables = [
            '{{increment_id}}' => $incrementId,
            '{{tracking}}' => implode(', ', $tracks),
            '{{store_name}}' => $this->storeManager->getStore($storeId)->getName(),
        ];

        return str_replace(array_keys($variables), array_values($variables), $template);
    }
}
