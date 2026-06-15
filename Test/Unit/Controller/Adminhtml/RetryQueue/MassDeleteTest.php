<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Test\Unit\Controller\Adminhtml\RetryQueue;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Ui\Component\MassAction\Filter;
use Muon\SMSNotification\Controller\Adminhtml\RetryQueue\MassDelete;
use Muon\SMSNotification\Model\ResourceModel\RetryQueue as RetryQueueResource;
use Muon\SMSNotification\Model\ResourceModel\RetryQueue\Collection;
use Muon\SMSNotification\Model\ResourceModel\RetryQueue\CollectionFactory;
use Muon\SMSNotification\Model\RetryQueue;
use PHPUnit\Framework\TestCase;

class MassDeleteTest extends TestCase
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

        $this->controller = new MassDelete(
            $context,
            $this->filterMock,
            $this->collectionFactoryMock,
            $this->resourceMock
        );
    }

    public function testExecuteDeletesSelectedRows(): void
    {
        $item1 = $this->createMock(RetryQueue::class);
        $item2 = $this->createMock(RetryQueue::class);

        $collection = $this->createMock(Collection::class);
        $collection->method('getIterator')->willReturn(new \ArrayIterator([$item1, $item2]));
        $this->collectionFactoryMock->method('create')->willReturn($collection);
        $this->filterMock->method('getCollection')->with($collection)->willReturn($collection);

        $deleted = [];
        $this->resourceMock->expects($this->exactly(2))
            ->method('delete')
            ->willReturnCallback(function ($item) use (&$deleted) {
                $deleted[] = $item;
                return $this->resourceMock;
            });

        $this->messageManagerMock->expects($this->once())->method('addSuccessMessage');
        $this->messageManagerMock->expects($this->never())->method('addErrorMessage');

        $this->assertSame($this->redirectMock, $this->controller->execute());
        $this->assertSame([$item1, $item2], $deleted);
    }
}
