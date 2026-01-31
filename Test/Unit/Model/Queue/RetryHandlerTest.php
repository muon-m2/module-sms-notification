<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Test\Unit\Model\Queue;

use PHPUnit\Framework\TestCase;
use Muon\SMSNotification\Model\Queue\RetryHandler;
use Muon\SMSNotification\Model\Config;
use Magento\Framework\Serialize\Serializer\Json;
use Muon\SMSNotification\Model\ResourceModel\RetryQueue as RetryQueueResource;
use Muon\SMSNotification\Model\RetryQueueFactory;
use Muon\SMSNotification\Model\RetryQueue;
use Psr\Log\LoggerInterface;
use Muon\SMSNotification\Api\Data\MessageInterface;

class RetryHandlerTest extends TestCase
{
    private $configMock;
    private $retryQueueFactoryMock;
    private $retryQueueResourceMock;
    private $serializerMock;
    private $loggerMock;
    private $retryHandler;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->retryQueueFactoryMock = $this->getMockBuilder(RetryQueueFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->retryQueueResourceMock = $this->createMock(RetryQueueResource::class);
        $this->serializerMock = $this->createMock(Json::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->retryHandler = new RetryHandler(
            $this->configMock,
            $this->retryQueueFactoryMock,
            $this->retryQueueResourceMock,
            $this->serializerMock,
            $this->loggerMock
        );
    }

    public function testHandleRetrySavesToQueue(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $message->method('getAttemptNumber')->willReturnOnConsecutiveCalls(1, 2, 2);
        $message->method('getStoreId')->willReturn(1);
        $message->method('getPhone')->willReturn('+1234567890');
        $message->method('getMessage')->willReturn('Test message');

        $this->configMock->method('getNumberAttempts')->with(1)->willReturn(3);
        $this->configMock->method('getRetryDelay')->with(1)->willReturn(60);

        $retryEntryMock = $this->createMock(RetryQueue::class);
        $this->retryQueueFactoryMock->method('create')->willReturn($retryEntryMock);

        $this->serializerMock->expects($this->once())
            ->method('serialize')
            ->with($this->callback(function ($data) {
                return $data['attempt_number'] === 2 && $data['phone'] === '+1234567890';
            }))
            ->willReturn('{"payload": "json"}');

        $retryEntryMock->expects($this->once())
            ->method('setData')
            ->with($this->callback(function ($data) {
                return $data['message_payload'] === '{"payload": "json"}' && isset($data['scheduled_at']);
            }))
            ->willReturnSelf();

        $this->retryQueueResourceMock->expects($this->once())
            ->method('save')
            ->with($retryEntryMock);

        $message->expects($this->once())->method('setAttemptNumber')->with(2);
        $this->loggerMock->expects($this->once())->method('info');

        $this->retryHandler->handle($message);
    }

    public function testHandleExhausted(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $message->method('getAttemptNumber')->willReturn(3);
        $message->method('getStoreId')->willReturn(1);
        $message->method('getPhone')->willReturn('+1234567890');

        $this->configMock->method('getNumberAttempts')->with(1)->willReturn(3);

        $this->retryQueueFactoryMock->expects($this->never())->method('create');
        $this->loggerMock->expects($this->once())->method('critical');

        $this->retryHandler->handle($message);
    }
}
