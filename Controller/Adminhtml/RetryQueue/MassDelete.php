<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Controller\Adminhtml\RetryQueue;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Ui\Component\MassAction\Filter;
use Muon\SMSNotification\Model\ResourceModel\RetryQueue as RetryQueueResource;
use Muon\SMSNotification\Model\ResourceModel\RetryQueue\CollectionFactory;

/**
 * Deletes the selected retry-queue rows.
 */
class MassDelete extends Action
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
            $deleted = 0;
            foreach ($collection as $item) {
                $this->retryQueueResource->delete($item);
                $deleted++;
            }
            $this->messageManager->addSuccessMessage(
                (string)__('%1 record(s) have been deleted.', $deleted)
            );
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $resultRedirect->setPath('*/*/');
    }
}
