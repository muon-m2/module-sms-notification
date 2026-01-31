<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Test\Unit\Model\Queue\Handler;

use PHPUnit\Framework\TestCase;
use Muon\SMSNotification\Model\Queue\Handler\Handler;
use Muon\SMSNotification\Api\SmsTransportInterface;
use Muon\SMSNotification\Model\Queue\RetryHandler;
use Psr\Log\LoggerInterface;
use Muon\SMSNotification\Model\PhoneValidator;
use Muon\SMSNotification\Api\Data\MessageInterface;
use Muon\SMSNotification\Exception\SmsTransportException;

class HandlerTest extends TestCase
{
    private $smsTransportMock;
    private $retryHandlerMock;
    private $loggerMock;
    private $phoneValidatorMock;
    private $handler;

    protected function setUp(): void
    {
        $this->smsTransportMock = $this->createMock(SmsTransportInterface::class);
        $this->retryHandlerMock = $this->createMock(RetryHandler::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->phoneValidatorMock = $this->createMock(PhoneValidator::class);

        $this->handler = new Handler(
            $this->smsTransportMock,
            $this->retryHandlerMock,
            $this->loggerMock,
            $this->phoneValidatorMock
        );
    }

    public function testExecuteSuccess(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $message->method('getPhone')->willReturn('+1234567890');
        $message->method('getMessage')->willReturn('Test Message');
        $message->method('getStoreId')->willReturn(1);

        $this->phoneValidatorMock->method('isValid')->with('+1234567890')->willReturn(true);

        $this->smsTransportMock->expects($this->once())
            ->method('send')
            ->with('+1234567890', 'Test Message', 1);

        $this->loggerMock->expects($this->once())->method('info')->with('SMS sent');

        $this->handler->execute($message);
    }

    public function testExecuteInvalidPhone(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $message->method('getPhone')->willReturn('invalid');

        $this->phoneValidatorMock->method('isValid')->with('invalid')->willReturn(false);

        $this->smsTransportMock->expects($this->never())->method('send');
        $this->loggerMock->expects($this->once())->method('error');
        $this->retryHandlerMock->expects($this->once())->method('handle')->with($message);

        $this->handler->execute($message);
    }

    public function testExecuteRetry(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $message->method('getPhone')->willReturn('+1234567890');
        $message->method('getMessage')->willReturn('Test Message');
        $message->method('getStoreId')->willReturn(1);

        $this->phoneValidatorMock->method('isValid')->willReturn(true);

        $this->smsTransportMock->method('send')->willThrowException(new SmsTransportException('Failed'));

        $this->retryHandlerMock->expects($this->once())->method('handle')->with($message);

        $this->handler->execute($message);
    }
}
