<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Test\Unit\Controller\Adminhtml\RetryQueue;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Ui\Component\MassAction\Filter;
use Muon\SMSNotification\Controller\Adminhtml\RetryQueue\MassReQueue;
use Muon\SMSNotification\Model\ResourceModel\RetryQueue as RetryQueueResource;
use Muon\SMSNotification\Model\ResourceModel\RetryQueue\Collection;
use Muon\SMSNotification\Model\ResourceModel\RetryQueue\CollectionFactory;
use Muon\SMSNotification\Model\RetryQueue;
use PHPUnit\Framework\TestCase;

class MassReQueueTest extends TestCase
{
    private $filterMock;
    private $collectionFactoryMock;
    private $resourceMock;
    private $messageManagerMock;
    private $redirectMock;
    private $controller;

    protected function setUp(): void
    {
        $this->filterMock = $this->createMock(Filter::class);
        $this->collectionFactoryMock = $this->createMock(CollectionFactory::class);
        $this->resourceMock = $this->createMock(RetryQueueResource::class);
        $this->messageManagerMock = $this->createMock(ManagerInterface::class);

        $this->redirectMock = $this->createMock(Redirect::class);
        $this->redirectMock->method('setPath')->willReturnSelf();
        $redirectFactory = $this->createMock(RedirectFactory::class);
        $redirectFactory->method('create')->willReturn($this->redirectMock);

        $context = $this->createMock(Context::class);
        $context->method('getMessageManager')->willReturn($this->messageManagerMock);
        $context->method('getResultRedirectFactory')->willReturn($redirectFactory);

        $this->controller = new MassReQueue(
            $context,
            $this->filterMock,
            $this->collectionFactoryMock,
            $this->resourceMock
        );
    }

    public function testExecuteResetsRowsToPendingWithFreshBudget(): void
    {
        $item = $this->createMock(RetryQueue::class);
        $captured = [];
        $item->method('setData')->willReturnCallback(function ($key, $value) use (&$captured, $item) {
            $captured[$key] = $value;
            return $item;
        });

        $collection = $this->createMock(Collection::class);
        $collection->method('getIterator')->willReturn(new \ArrayIterator([$item]));
        $this->collectionFactoryMock->method('create')->willReturn($collection);
        $this->filterMock->method('getCollection')->with($collection)->willReturn($collection);

        $this->resourceMock->expects($this->once())->method('save')->with($item);
        $this->messageManagerMock->expects($this->once())->method('addSuccessMessage');

        $this->assertSame($this->redirectMock, $this->controller->execute());

        // Re-queue resets to pending with a fresh attempt budget and clears lock/error.
        $this->assertSame(RetryQueue::STATUS_PENDING, $captured['status']);
        $this->assertSame(1, $captured['attempt_number']);
        $this->assertNull($captured['claim_token']);
        $this->assertNull($captured['locked_at']);
        $this->assertNull($captured['last_error']);
    }
}
