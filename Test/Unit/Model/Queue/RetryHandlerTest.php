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
use Muon\SMSNotification\Model\Data\Message;
use Psr\Log\LoggerInterface;

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

    private function makeMessage(int $attempt, int $storeId): Message
    {
        $message = new Message();
        $message->setAttemptNumber($attempt);
        $message->setStoreId($storeId);
        $message->setPhone('+14155552671');
        $message->setMessage('Test message');

        return $message;
    }

    public function testHandleSchedulesRetryWhenAttemptsRemain(): void
    {
        $message = $this->makeMessage(1, 1);

        $this->configMock->method('getNumberAttempts')->with(1)->willReturn(3);
        $this->configMock->method('getRetryDelay')->with(1)->willReturn(60);
        $this->serializerMock->method('serialize')->willReturn('{"payload":"json"}');

        $retryEntryMock = $this->createMock(RetryQueue::class);
        $this->retryQueueFactoryMock->method('create')->willReturn($retryEntryMock);

        $retryEntryMock->expects($this->once())
            ->method('setData')
            ->with($this->callback(static function (array $data): bool {
                return $data['status'] === RetryQueue::STATUS_PENDING
                    && $data['attempt_number'] === 2
                    && $data['store_id'] === 1
                    && $data['message_payload'] === '{"payload":"json"}'
                    && isset($data['scheduled_at']);
            }))
            ->willReturnSelf();

        $this->retryQueueResourceMock->expects($this->once())->method('save')->with($retryEntryMock);
        $this->loggerMock->expects($this->once())->method('info');

        $this->retryHandler->handle($message, 'transport error');

        $this->assertSame(2, $message->getAttemptNumber());
    }

    public function testHandleMovesToDeadLetterWhenExhausted(): void
    {
        $message = $this->makeMessage(3, 1);

        $this->configMock->method('getNumberAttempts')->with(1)->willReturn(3);
        $this->serializerMock->method('serialize')->willReturn('{"payload":"dead"}');

        $retryEntryMock = $this->createMock(RetryQueue::class);
        // Unlike the old behaviour (log-only), exhaustion must persist a dead-letter row.
        $this->retryQueueFactoryMock->expects($this->once())->method('create')->willReturn($retryEntryMock);

        $retryEntryMock->expects($this->once())
            ->method('setData')
            ->with($this->callback(static function (array $data): bool {
                return $data['status'] === RetryQueue::STATUS_DEAD
                    && $data['attempt_number'] === 3
                    && $data['last_error'] === 'permanent failure';
            }))
            ->willReturnSelf();

        $this->retryQueueResourceMock->expects($this->once())->method('save')->with($retryEntryMock);
        $this->loggerMock->expects($this->once())->method('critical');

        $this->retryHandler->handle($message, 'permanent failure');
    }

    public function testDeferReQueuesWithoutConsumingBudget(): void
    {
        $message = $this->makeMessage(2, 1);

        $this->serializerMock->method('serialize')->willReturn('{"payload":"deferred"}');

        $retryEntryMock = $this->createMock(RetryQueue::class);
        $this->retryQueueFactoryMock->method('create')->willReturn($retryEntryMock);

        $retryEntryMock->expects($this->once())
            ->method('setData')
            ->with($this->callback(static function (array $data): bool {
                // Deferral keeps the same attempt number (not a failure).
                return $data['status'] === RetryQueue::STATUS_PENDING && $data['attempt_number'] === 2;
            }))
            ->willReturnSelf();

        $this->retryQueueResourceMock->expects($this->once())->method('save')->with($retryEntryMock);
        $this->loggerMock->expects($this->once())->method('info');

        $this->retryHandler->defer($message, 60);

        $this->assertSame(2, $message->getAttemptNumber());
    }
}
