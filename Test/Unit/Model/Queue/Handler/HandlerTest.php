<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Test\Unit\Model\Queue\Handler;

use PHPUnit\Framework\TestCase;
use Muon\SMSNotification\Model\Queue\Handler\Handler;
use Muon\SMSNotification\Api\SmsTransportInterface;
use Muon\SMSNotification\Model\Queue\RetryHandler;
use Psr\Log\LoggerInterface;
use Muon\SMSNotification\Model\PhoneValidator;
use Muon\SMSNotification\Model\RateLimiter;
use Muon\SMSNotification\Api\Data\MessageInterface;
use Muon\SMSNotification\Exception\SmsTransportException;

class HandlerTest extends TestCase
{
    private $smsTransportMock;
    private $retryHandlerMock;
    private $loggerMock;
    private $phoneValidatorMock;
    private $rateLimiterMock;
    private $handler;

    protected function setUp(): void
    {
        $this->smsTransportMock = $this->createMock(SmsTransportInterface::class);
        $this->retryHandlerMock = $this->createMock(RetryHandler::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->phoneValidatorMock = $this->createMock(PhoneValidator::class);
        $this->rateLimiterMock = $this->createMock(RateLimiter::class);
        // Default: not rate limited, so the standard send path runs.
        $this->rateLimiterMock->method('tryAcquire')->willReturn(true);

        $this->handler = new Handler(
            $this->smsTransportMock,
            $this->retryHandlerMock,
            $this->loggerMock,
            $this->phoneValidatorMock,
            $this->rateLimiterMock
        );
    }

    public function testExecuteDefersWhenRateLimited(): void
    {
        $rateLimiter = $this->createMock(RateLimiter::class);
        $rateLimiter->method('tryAcquire')->willReturn(false);
        $handler = new Handler(
            $this->smsTransportMock,
            $this->retryHandlerMock,
            $this->loggerMock,
            $this->phoneValidatorMock,
            $rateLimiter
        );

        $message = $this->createMock(MessageInterface::class);
        $message->method('getPhone')->willReturn('+14155552671');
        $message->method('getStoreId')->willReturn(1);
        $message->method('getAttemptNumber')->willReturn(1);
        $this->phoneValidatorMock->method('isValid')->willReturn(true);

        // Rate-limited: defer (no budget consumed), never send.
        $this->smsTransportMock->expects($this->never())->method('send');
        $this->retryHandlerMock->expects($this->once())->method('defer')->with($message, $this->isType('int'));

        $handler->execute($message);
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

    public function testExecuteInvalidPhoneIsPermanentAndNotRetried(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $message->method('getPhone')->willReturn('invalid');
        $message->method('getAttemptNumber')->willReturn(1);
        $message->method('getStoreId')->willReturn(1);

        $this->phoneValidatorMock->method('isValid')->with('invalid')->willReturn(false);

        $this->smsTransportMock->expects($this->never())->method('send');
        // Invalid phone is a permanent failure: it must NOT be scheduled for retry.
        $this->retryHandlerMock->expects($this->never())->method('handle');
        $this->loggerMock->expects($this->once())->method('warning');

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

    public function testExecuteRetriesOnAnyThrowable(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $message->method('getPhone')->willReturn('+1234567890');
        $message->method('getMessage')->willReturn('Test Message');
        $message->method('getStoreId')->willReturn(1);

        $this->phoneValidatorMock->method('isValid')->willReturn(true);

        // A non-SmsTransportException (e.g. network/SDK error or misconfiguration) must
        // still be caught and routed to retry rather than escaping the consumer.
        $this->smsTransportMock->method('send')
            ->willThrowException(new \RuntimeException('Unexpected SDK failure'));

        $this->retryHandlerMock->expects($this->once())->method('handle')->with($message);

        $this->handler->execute($message);
    }

    public function testLoggerContextMasksPhoneAndOmitsBody(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $message->method('getPhone')->willReturn('+14155552671');
        $message->method('getMessage')->willReturn('Sensitive body');
        $message->method('getStoreId')->willReturn(1);
        $message->method('getAttemptNumber')->willReturn(1);

        $this->phoneValidatorMock->method('isValid')->willReturn(true);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with(
                'SMS sent',
                $this->callback(static function (array $context): bool {
                    return $context['phone'] === '********2671'
                        && !array_key_exists('message', $context);
                })
            );

        $this->handler->execute($message);
    }
}
