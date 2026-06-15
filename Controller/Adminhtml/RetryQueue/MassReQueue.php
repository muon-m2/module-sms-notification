<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Controller\Adminhtml\RetryQueue;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Ui\Component\MassAction\Filter;
use Muon\SMSNotification\Model\ResourceModel\RetryQueue as RetryQueueResource;
use Muon\SMSNotification\Model\ResourceModel\RetryQueue\CollectionFactory;
use Muon\SMSNotification\Model\RetryQueue;

/**
 * Resets the selected rows to "pending" with a fresh attempt budget so the cron
 * republishes them on its next run.
 */
class MassReQueue extends Action
{
    public const ADMIN_RESOURCE = 'Muon_SMSNotification::retry_queue';

    /**
     * @param Context            $context
     * @param Filter             $filter
     * @param CollectionFactory  $collectionFactory
     * @param RetryQueueResource $retryQueueResource
     */
    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly RetryQueueResource $retryQueueResource
    ) {
        parent::__construct($context);
    }

    /**
     * @return Redirect
     */
    public function execute(): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $requeued = 0;
            foreach ($collection as $item) {
                $item->setData('status', RetryQueue::STATUS_PENDING);
                $item->setData('attempt_number', 1);
                $item->setData('scheduled_at', date('Y-m-d H:i:s'));
                $item->setData('claim_token', null);
                $item->setData('locked_at', null);
                $item->setData('last_error', null);
                $this->retryQueueResource->save($item);
                $requeued++;
            }
            $this->messageManager->addSuccessMessage(
                (string)__('%1 message(s) have been re-queued for delivery.', $requeued)
            );
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $resultRedirect->setPath('*/*/');
    }
}
