<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Test\Unit\Service;

use PHPUnit\Framework\TestCase;
use Muon\SMSNotification\Service\Notifier;
use Magento\Framework\MessageQueue\PublisherInterface;
use Muon\SMSNotification\Api\Data\MessageInterfaceFactory;
use Muon\SMSNotification\Api\Data\MessageInterface;
use Muon\SMSNotification\Model\PhoneValidator;
use Muon\SMSNotification\Model\Config;
use Psr\Log\LoggerInterface;

class NotifierTest extends TestCase
{
    private $publisherMock;
    private $messageFactoryMock;
    private $phoneValidatorMock;
    private $configMock;
    private $loggerMock;
    private $notifier;

    protected function setUp(): void
    {
        $this->publisherMock = $this->createMock(PublisherInterface::class);
        $this->messageFactoryMock = $this->getMockBuilder(MessageInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->phoneValidatorMock = $this->createMock(PhoneValidator::class);
        $this->configMock = $this->createMock(Config::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->notifier = new Notifier(
            $this->publisherMock,
            $this->messageFactoryMock,
            $this->phoneValidatorMock,
            $this->configMock,
            $this->loggerMock
        );
    }

    public function testSendSMSSuccess(): void
    {
        $phone = '+1234567890';
        $message = 'Test Message';
        $storeId = 1;

        $this->phoneValidatorMock->method('isValid')->with($phone)->willReturn(true);

        $messageObjMock = $this->createMock(MessageInterface::class);
        $this->messageFactoryMock->method('create')->willReturn($messageObjMock);

        $messageObjMock->expects($this->once())->method('setPhone')->with($phone)->willReturnSelf();
        $messageObjMock->expects($this->once())->method('setMessage')->with($message)->willReturnSelf();
        $messageObjMock->expects($this->once())->method('setAttemptNumber')->with(1)->willReturnSelf();
        $messageObjMock->expects($this->once())->method('setStoreId')->with($storeId)->willReturnSelf();

        $this->configMock->expects($this->once())->method('getQueueConnection')->with($storeId)->willReturn('db');
        $this->publisherMock->expects($this->once())
            ->method('publish')
            ->with('muon.sms', $messageObjMock);

        $this->notifier->sendSMS($phone, $message, $storeId);
    }

    public function testSendSMSInvalidPhone(): void
    {
        $phone = 'invalid';
        $message = 'Test Message';

        $this->phoneValidatorMock->method('isValid')->with($phone)->willReturn(false);

        $this->loggerMock->expects($this->once())->method('error');
        $this->publisherMock->expects($this->never())->method('publish');

        $this->notifier->sendSMS($phone, $message);
    }
}
