<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Controller\Adminhtml\Test;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Muon\SMSNotification\Api\SmsTransportInterface;
use Muon\SMSNotification\Model\Config;

/**
 * Sends a test SMS through the currently configured transport to the configured recipient.
 */
class SendSms extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Muon_SMSNotification::general';

    /**
     * @param Context               $context
     * @param JsonFactory           $resultJsonFactory
     * @param SmsTransportInterface $transport
     * @param Config                $config
     */
    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly SmsTransportInterface $transport,
        private readonly Config $config
    ) {
        parent::__construct($context);
    }

    /**
     * @return Json
     */
    public function execute(): Json
    {
        $result = $this->resultJsonFactory->create();

        try {
            $phone = $this->config->getSendToPhone();
            if (trim($phone) === '') {
                throw new \RuntimeException(
                    (string)__('Please set and save a "Send To Phone Number" before testing.')
                );
            }

            $this->transport->send($phone, (string)__('Muon SMS Notification: this is a test message.'), null);

            return $result->setData([
                'success' => true,
                'message' => (string)__('Test SMS sent to %1.', $phone),
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
