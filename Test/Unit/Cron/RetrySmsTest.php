<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Test\Unit\Cron;

use PHPUnit\Framework\TestCase;
use Muon\SMSNotification\Cron\RetrySms;
use Muon\SMSNotification\Model\ResourceModel\RetryQueue\CollectionFactory;
use Muon\SMSNotification\Model\ResourceModel\RetryQueue\Collection;
use Muon\SMSNotification\Model\ResourceModel\RetryQueue as RetryQueueResource;
use Muon\SMSNotification\Model\RetryQueue;
use Muon\SMSNotification\Api\Data\MessageInterfaceFactory;
use Muon\SMSNotification\Api\Data\MessageInterface;
use Muon\SMSNotification\Model\Config;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

class RetrySmsTest extends TestCase
{
    private $collectionFactoryMock;
    private $retryQueueResourceMock;
    private $messageFactoryMock;
    private $publisherMock;
    private $configMock;
    private $serializerMock;
    private $loggerMock;
    private $cron;

    protected function setUp(): void
    {
        $this->collectionFactoryMock = $this->createMock(CollectionFactory::class);
        $this->retryQueueResourceMock = $this->createMock(RetryQueueResource::class);
        $this->messageFactoryMock = $this->getMockBuilder(MessageInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->publisherMock = $this->createMock(PublisherInterface::class);
        $this->configMock = $this->createMock(Config::class);
        $this->serializerMock = $this->createMock(Json::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->cron = new RetrySms(
            $this->collectionFactoryMock,
            $this->retryQueueResourceMock,
            $this->messageFactoryMock,
            $this->publisherMock,
            $this->configMock,
            $this->serializerMock,
            $this->loggerMock
        );
    }

    private function makeEntry(): \PHPUnit\Framework\MockObject\MockObject
    {
        $entry = $this->createMock(RetryQueue::class);
        $entry->method('getData')->willReturnMap([
            ['message_payload', '{"payload":"json"}'],
            ['store_id', 1],
            ['attempt_number', 2],
        ]);

        return $entry;
    }

    public function testExecuteSkipsWhenNothingClaimed(): void
    {
        $this->configMock->method('getRetryBatchSize')->willReturn(100);
        $this->retryQueueResourceMock->expects($this->once())
            ->method('claimBatch')
            ->with($this->isType('string'), 100, $this->isType('int'))
            ->willReturn(0);

        $this->collectionFactoryMock->expects($this->never())->method('create');
        $this->publisherMock->expects($this->never())->method('publish');

        $this->cron->execute();
    }

    public function testExecuteClaimsBatchAndRepublishes(): void
    {
        $this->configMock->method('getRetryBatchSize')->willReturn(100);
        $this->retryQueueResourceMock->method('claimBatch')->willReturn(1);

        $entry = $this->makeEntry();
        $collectionMock = $this->createMock(Collection::class);
        $this->collectionFactoryMock->method('create')->willReturn($collectionMock);
        $collectionMock->expects($this->once())->method('addFieldToFilter')->with('claim_token', $this->anything());
        $collectionMock->method('getIterator')->willReturn(new \ArrayIterator([$entry]));

        $this->serializerMock->method('unserialize')->willReturn([
            'message' => 'Test',
            'phone' => '+14155552671',
            'attempt_number' => 2,
            'store_id' => 1,
        ]);

        $messageMock = $this->createMock(MessageInterface::class);
        $this->messageFactoryMock->method('create')->willReturn($messageMock);
        $messageMock->expects($this->once())->method('setMessage')->with('Test');
        $messageMock->expects($this->once())->method('setPhone')->with('+14155552671');
        $messageMock->expects($this->once())->method('setAttemptNumber')->with(2);
        $messageMock->expects($this->once())->method('setStoreId')->with(1);

        $this->configMock->method('getQueueConnection')->with(1)->willReturn('db');
        $this->publisherMock->expects($this->once())->method('publish')->with('muon.sms', $messageMock);
        $this->retryQueueResourceMock->expects($this->once())->method('delete')->with($entry);

        $this->cron->execute();
    }

    public function testExecuteMarksFailedOnPublishError(): void
    {
        $this->configMock->method('getRetryBatchSize')->willReturn(100);
        $this->retryQueueResourceMock->method('claimBatch')->willReturn(1);

        $entry = $this->makeEntry();
        $collectionMock = $this->createMock(Collection::class);
        $this->collectionFactoryMock->method('create')->willReturn($collectionMock);
        $collectionMock->method('getIterator')->willReturn(new \ArrayIterator([$entry]));

        $this->serializerMock->method('unserialize')->willReturn([
            'message' => 'Test',
            'phone' => '+14155552671',
            'attempt_number' => 2,
            'store_id' => 1,
        ]);

        $messageMock = $this->createMock(MessageInterface::class);
        $this->messageFactoryMock->method('create')->willReturn($messageMock);
        $this->configMock->method('getQueueConnection')->willReturn('db');
        $this->publisherMock->method('publish')
            ->willThrowException(new \RuntimeException('Broker down'));

        // The row must be released back to "failed" (not deleted) so it is not lost.
        $entry->expects($this->exactly(4))->method('setData')
            ->willReturnCallback(static function (string $key, $value) {
                if ($key === 'status') {
                    self::assertSame(RetryQueue::STATUS_FAILED, $value);
                }
                return null;
            });
        $this->retryQueueResourceMock->expects($this->once())->method('save')->with($entry);
        $this->retryQueueResourceMock->expects($this->never())->method('delete');
        $this->loggerMock->expects($this->once())->method('error');

        $this->cron->execute();
    }
}
