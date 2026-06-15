<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Test\Unit\Observer;

use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Sales\Api\Data\OrderInterface;
use Muon\SMSNotification\Api\MessageBuilderInterface;
use Muon\SMSNotification\Api\NotifierInterface;
use Muon\SMSNotification\Model\Config;
use Muon\SMSNotification\Observer\SalesOrderPlaceAfterObserver;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SalesOrderPlaceAfterObserverTest extends TestCase
{
    private $notifierMock;
    private $configMock;
    private $messageBuilderMock;
    private $loggerMock;
    private $observer;

    protected function setUp(): void
    {
        $this->notifierMock = $this->createMock(NotifierInterface::class);
        $this->configMock = $this->createMock(Config::class);
        $this->messageBuilderMock = $this->createMock(MessageBuilderInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->observer = new SalesOrderPlaceAfterObserver(
            $this->notifierMock,
            $this->configMock,
            $this->messageBuilderMock,
            $this->loggerMock
        );
    }

    private function buildEvent(?OrderInterface $order): Observer
    {
        $event = $this->createMock(Event::class);
        $event->method('getData')->with('order')->willReturn($order);

        $observer = $this->createMock(Observer::class);
        $observer->method('getEvent')->willReturn($event);

        return $observer;
    }

    public function testSendsWhenEnabled(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getStoreId')->willReturn(1);

        $this->configMock->method('isOrderEnabled')->with(1)->willReturn(true);
        $this->configMock->method('getSendToPhone')->with(1)->willReturn('+14155552671');
        $this->messageBuilderMock->method('getMessage')->with($order)->willReturn('Order placed');

        $this->notifierMock->expects($this->once())
            ->method('sendSMS')
            ->with('+14155552671', 'Order placed', 1);

        $this->observer->execute($this->buildEvent($order));
    }

    public function testSkippedWhenDisabled(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getStoreId')->willReturn(1);

        $this->configMock->method('isOrderEnabled')->with(1)->willReturn(false);

        $this->notifierMock->expects($this->never())->method('sendSMS');

        $this->observer->execute($this->buildEvent($order));
    }

    public function testSwallowsNotifierExceptionSoCheckoutNeverBreaks(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getStoreId')->willReturn(1);

        $this->configMock->method('isOrderEnabled')->with(1)->willReturn(true);
        $this->configMock->method('getSendToPhone')->willReturn('+14155552671');
        $this->messageBuilderMock->method('getMessage')->willReturn('Order placed');

        $this->notifierMock->method('sendSMS')
            ->willThrowException(new \RuntimeException('Queue broker unavailable'));

        $this->loggerMock->expects($this->once())->method('error');

        // Must not throw — a notification failure cannot be allowed to roll back the order.
        $this->observer->execute($this->buildEvent($order));
    }
}
