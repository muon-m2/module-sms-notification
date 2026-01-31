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
        $this->messageFactoryMock = $this->createMock(MessageInterfaceFactory::class);
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

    public function testExecuteProcessesItems(): void
    {
        $collectionMock = $this->createMock(Collection::class);
        $this->collectionFactoryMock->method('create')->willReturn($collectionMock);

        $retryEntryMock = $this->getMockBuilder(RetryQueue::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData', 'delete'])
            ->getMock();
        $collectionMock->method('getIterator')->willReturn(new \ArrayIterator([$retryEntryMock]));
        $collectionMock->method('getSize')->willReturn(1);

        $retryEntryMock->method('getData')->with('message_payload')->willReturn('{"payload": "json"}');
        $this->serializerMock->method('unserialize')->willReturn([
            'message' => 'Test',
            'phone' => '+123',
            'attempt_number' => 2,
            'store_id' => 1
        ]);

        $messageMock = $this->createMock(MessageInterface::class);
        $this->messageFactoryMock->method('create')->willReturn($messageMock);

        $messageMock->expects($this->once())->method('setMessage')->with('Test');
        $messageMock->expects($this->once())->method('setPhone')->with('+123');
        $messageMock->expects($this->once())->method('setAttemptNumber')->with(2);
        $messageMock->expects($this->once())->method('setStoreId')->with(1);

        $this->configMock->expects($this->once())->method('getQueueConnection')->with(1)->willReturn('db');
        $this->publisherMock->expects($this->once())->method('publish')->with('muon.sms', $messageMock);

        $this->retryQueueResourceMock->expects($this->once())
            ->method('delete')
            ->with($retryEntryMock);

        $this->cron->execute();
    }
}
