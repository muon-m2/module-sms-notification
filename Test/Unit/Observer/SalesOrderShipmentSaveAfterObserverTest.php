<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Test\Unit\Observer;

use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Shipment;
use Muon\SMSNotification\Api\MessageBuilderInterface;
use Muon\SMSNotification\Api\NotifierInterface;
use Muon\SMSNotification\Model\Config;
use Muon\SMSNotification\Model\RecipientResolver;
use Muon\SMSNotification\Observer\SalesOrderShipmentSaveAfterObserver;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SalesOrderShipmentSaveAfterObserverTest extends TestCase
{
    private $notifierMock;
    private $configMock;
    private $messageBuilderMock;
    private $recipientResolverMock;
    private $loggerMock;
    private $observer;

    protected function setUp(): void
    {
        $this->notifierMock = $this->createMock(NotifierInterface::class);
        $this->configMock = $this->createMock(Config::class);
        $this->messageBuilderMock = $this->createMock(MessageBuilderInterface::class);
        $this->recipientResolverMock = $this->createMock(RecipientResolver::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->observer = new SalesOrderShipmentSaveAfterObserver(
            $this->notifierMock,
            $this->configMock,
            $this->messageBuilderMock,
            $this->recipientResolverMock,
            $this->loggerMock
        );
    }

    private function buildEvent(?Shipment $shipment): Observer
    {
        $event = $this->createMock(Event::class);
        $event->method('getData')->with('shipment')->willReturn($shipment);

        $observer = $this->createMock(Observer::class);
        $observer->method('getEvent')->willReturn($event);

        return $observer;
    }

    private function shipmentWithOrder(): Shipment
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getStoreId')->willReturn(1);

        $shipment = $this->createMock(Shipment::class);
        $shipment->method('getOrder')->willReturn($order);

        $this->recipientResolverMock->method('resolveForOrder')->with($order)->willReturn('+14155550000');

        return $shipment;
    }

    public function testSendsWhenEnabled(): void
    {
        $shipment = $this->shipmentWithOrder();
        $this->configMock->method('isShipmentEnabled')->with(1)->willReturn(true);
        $this->messageBuilderMock->method('getMessage')->with($shipment)->willReturn('Shipped');

        $this->notifierMock->expects($this->once())
            ->method('sendSMS')
            ->with('+14155550000', 'Shipped', 1);

        $this->observer->execute($this->buildEvent($shipment));
    }

    public function testSkippedWhenDisabled(): void
    {
        $shipment = $this->shipmentWithOrder();
        $this->configMock->method('isShipmentEnabled')->with(1)->willReturn(false);

        $this->notifierMock->expects($this->never())->method('sendSMS');

        $this->observer->execute($this->buildEvent($shipment));
    }

    public function testSwallowsExceptionSoShipmentNeverBreaks(): void
    {
        $shipment = $this->shipmentWithOrder();
        $this->configMock->method('isShipmentEnabled')->with(1)->willReturn(true);
        $this->messageBuilderMock->method('getMessage')->willReturn('Shipped');
        $this->notifierMock->method('sendSMS')->willThrowException(new \RuntimeException('boom'));

        $this->loggerMock->expects($this->once())->method('error');

        $this->observer->execute($this->buildEvent($shipment));
    }
}
