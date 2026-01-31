<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Muon\SMSNotification\Model\OrderMessageBuilder;
use Muon\SMSNotification\Model\Config;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;

class OrderMessageBuilderTest extends TestCase
{
    private $configMock;
    private $builder;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->builder = new OrderMessageBuilder($this->configMock);
    }

    public function testGetMessage(): void
    {
        $orderMock = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->method('getStoreId')->willReturn(1);
        $orderMock->method('getIncrementId')->willReturn('100000001');
        $orderMock->method('getShippingDescription')->willReturn('Flat Rate');
        $orderMock->method('getCustomerFirstname')->willReturn('John');
        $orderMock->method('getCustomerLastname')->willReturn('Doe');
        $orderMock->method('getGrandTotal')->willReturn(150.00);

        $itemMock = $this->createMock(OrderItemInterface::class);
        $itemMock->method('getQtyOrdered')->willReturn(2);
        $itemMock->method('getSku')->willReturn('TSHIRT-01');

        $orderMock->method('getAllVisibleItems')->willReturn([$itemMock]);

        $this->configMock->method('getOrderTemplate')->willReturn(
            'Order {{increment_id}} placed by {{customer_name}}. Items: {{items}}. Total: {{order_total}}'
        );

        $expectedMessage = 'Order 100000001 placed by John Doe. Items: 2 x TSHIRT-01. Total: 150';

        $this->assertEquals($expectedMessage, $this->builder->getMessage($orderMock));
    }

    public function testGetMessageInvalidData(): void
    {
        $this->assertEquals('Order data is not valid', $this->builder->getMessage(null));
    }
}
