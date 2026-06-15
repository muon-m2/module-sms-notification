<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Service;

use Muon\SMSNotification\Model\Config;
use Muon\SMSNotification\Model\MessageFormatter;
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
     * @param PublisherInterface      $publisher        An instance of the publisher interface for handling messages.
     * @param MessageInterfaceFactory $messageFactory   Factory responsible for creating message instances.
     * @param PhoneValidator          $phoneValidator   Validates phone numbers for compliance with required formats.
     * @param Config                  $config           The configuration instance providing necessary settings.
     * @param LoggerInterface         $logger           Logger instance for handling logging operations.
     * @param MessageFormatter        $messageFormatter Applies length limits and reports segment counts.
     *
     * @return void
     */
    public function __construct(
        private readonly PublisherInterface $publisher,
        private readonly MessageInterfaceFactory $messageFactory,
        private readonly PhoneValidator $phoneValidator,
        private readonly Config $config,
        private readonly LoggerInterface $logger,
        private readonly MessageFormatter $messageFormatter
    ) {
    }

    /**
     * @inheritDoc
     */
    public function sendSMS(string $phone, string $message, ?int $storeId = null): void
    {
        if (!$this->phoneValidator->isValid($phone)) {
            $this->logger->error('SMS not sent: invalid recipient phone number format');

            return;
        }

        $message = $this->messageFormatter->format($message, $storeId);
        $this->logger->info(
            'SMS queued for delivery',
            ['segments' => $this->messageFormatter->segmentCount($message), 'store_id' => (int)$storeId]
        );

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
