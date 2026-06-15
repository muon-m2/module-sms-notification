<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Controller\Adminhtml\RetryQueue;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * Renders the SMS retry-queue admin grid.
 */
class Index extends Action
{
    public const ADMIN_RESOURCE = 'Muon_SMSNotification::retry_queue';

    /**
     * @param Context     $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    /**
     * @return Page
     */
    public function execute(): Page
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(self::ADMIN_RESOURCE);
        $resultPage->getConfig()->getTitle()->prepend(__('SMS Retry Queue'));

        return $resultPage;
    }
}
