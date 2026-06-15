<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Test\Unit\Model;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\Data\ShipmentTrackInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Muon\SMSNotification\Model\Config;
use Muon\SMSNotification\Model\ShipmentMessageBuilder;
use PHPUnit\Framework\TestCase;

class ShipmentMessageBuilderTest extends TestCase
{
    private $configMock;
    private $orderRepositoryMock;
    private $storeManagerMock;
    private $builder;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);

        $this->builder = new ShipmentMessageBuilder(
            $this->configMock,
            $this->orderRepositoryMock,
            $this->storeManagerMock
        );
    }

    public function testReturnsErrorOnInvalidType(): void
    {
        $this->assertSame('Shipment data is not valid', $this->builder->getMessage(new \stdClass()));
    }

    public function testBuildsMessageWithPlaceholders(): void
    {
        $shipment = $this->createMock(ShipmentInterface::class);
        $shipment->method('getStoreId')->willReturn(1);
        $shipment->method('getOrderId')->willReturn(5);

        $track = $this->createMock(ShipmentTrackInterface::class);
        $track->method('getTrackNumber')->willReturn('1Z999');
        $shipment->method('getTracks')->willReturn([$track]);

        $this->configMock->method('getShipmentTemplate')
            ->willReturn('Order #{{increment_id}} shipped. Tracking: {{tracking}}. {{store_name}}');

        $order = $this->createMock(OrderInterface::class);
        $order->method('getIncrementId')->willReturn('000000123');
        $this->orderRepositoryMock->method('get')->with(5)->willReturn($order);

        $store = $this->createMock(StoreInterface::class);
        $store->method('getName')->willReturn('Default Store');
        $this->storeManagerMock->method('getStore')->with(1)->willReturn($store);

        $this->assertSame(
            'Order #000000123 shipped. Tracking: 1Z999. Default Store',
            $this->builder->getMessage($shipment)
        );
    }
}
