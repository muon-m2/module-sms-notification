<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Service;

use Muon\SMSNotification\Model\Config;
use Muon\SMSNotification\Model\PhoneValidator;
use Muon\SMSNotification\Model\Data\Message;
use Muon\SMSNotification\Api\NotifierInterface;
use Muon\SMSNotification\Api\Data\MessageInterfaceFactory;
use Magento\Framework\MessageQueue\PublisherInterface;
use Psr\Log\LoggerInterface;

class Notifier implements NotifierInterface
{
    /**
     * Constructor method.
     *
     * @param PublisherInterface      $publisher      An instance of the publisher interface for handling messages.
     * @param MessageInterfaceFactory $messageFactory Factory responsible for creating message instances.
     * @param PhoneValidator          $phoneValidator Validates phone numbers for compliance with required formats.
     * @param Config                  $config         The configuration instance providing necessary settings.
     * @param LoggerInterface         $logger         Logger instance for handling logging operations.
     *
     * @return void
     */
    public function __construct(
        private readonly PublisherInterface $publisher,
        private readonly MessageInterfaceFactory $messageFactory,
        private readonly PhoneValidator $phoneValidator,
        private readonly Config $config,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function sendSMS(string $phone, string $message, ?int $storeId = null): void
    {
        if (!$this->phoneValidator->isValid($phone)) {
            $this->logger->error((string)__('Invalid phone number format: %1', $phone));

            return;
        }
        $messageObj = $this->messageFactory->create();
        $messageObj->setPhone($phone);
        $messageObj->setMessage($message);
        $messageObj->setAttemptNumber(1);
        $messageObj->setStoreId((int)$storeId);

        $connection = $this->config->getQueueConnection($storeId);
        $topic = $connection === 'amqp' ? 'muon.sms.amqp' : 'muon.sms';

        $this->publisher->publish($topic, $messageObj);
    }
}
