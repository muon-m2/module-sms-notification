<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Test\Unit\Observer;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Muon\SMSNotification\Api\MessageBuilderInterface;
use Muon\SMSNotification\Api\NotifierInterface;
use Muon\SMSNotification\Model\Config;
use Muon\SMSNotification\Observer\CustomerRegisterSuccessObserver;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CustomerRegisterSuccessObserverTest extends TestCase
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

        $this->observer = new CustomerRegisterSuccessObserver(
            $this->notifierMock,
            $this->configMock,
            $this->messageBuilderMock,
            $this->loggerMock
        );
    }

    private function buildEvent(?CustomerInterface $customer): Observer
    {
        $event = $this->createMock(Event::class);
        $event->method('getData')->with('customer')->willReturn($customer);

        $observer = $this->createMock(Observer::class);
        $observer->method('getEvent')->willReturn($event);

        return $observer;
    }

    public function testSendsWhenEnabled(): void
    {
        $customer = $this->createMock(CustomerInterface::class);
        $customer->method('getStoreId')->willReturn(1);

        $this->configMock->method('isCustomerRegisterEnabled')->with(1)->willReturn(true);
        $this->configMock->method('getSendToPhone')->with(1)->willReturn('+14155552671');
        $this->messageBuilderMock->method('getMessage')->with($customer)->willReturn('Welcome');

        $this->notifierMock->expects($this->once())
            ->method('sendSMS')
            ->with('+14155552671', 'Welcome', 1);

        $this->observer->execute($this->buildEvent($customer));
    }

    public function testSkippedWhenDisabled(): void
    {
        $customer = $this->createMock(CustomerInterface::class);
        $customer->method('getStoreId')->willReturn(1);

        $this->configMock->method('isCustomerRegisterEnabled')->with(1)->willReturn(false);

        $this->notifierMock->expects($this->never())->method('sendSMS');

        $this->observer->execute($this->buildEvent($customer));
    }

    public function testSwallowsNotifierExceptionSoRegistrationNeverBreaks(): void
    {
        $customer = $this->createMock(CustomerInterface::class);
        $customer->method('getStoreId')->willReturn(1);

        $this->configMock->method('isCustomerRegisterEnabled')->with(1)->willReturn(true);
        $this->configMock->method('getSendToPhone')->willReturn('+14155552671');
        $this->messageBuilderMock->method('getMessage')->willReturn('Welcome');

        $this->notifierMock->method('sendSMS')
            ->willThrowException(new \RuntimeException('Queue broker unavailable'));

        $this->loggerMock->expects($this->once())->method('error');

        // Must not throw — a notification failure cannot be allowed to break registration.
        $this->observer->execute($this->buildEvent($customer));
    }
}
