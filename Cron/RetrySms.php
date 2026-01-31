<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Cron;

use Muon\SMSNotification\Model\ResourceModel\RetryQueue\CollectionFactory;
use Muon\SMSNotification\Model\ResourceModel\RetryQueue as RetryQueueResource;
use Muon\SMSNotification\Api\Data\MessageInterfaceFactory;
use Muon\SMSNotification\Model\Config;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

/**
 * Cron job for processing scheduled SMS retry attempts.
 */
class RetrySms
{
    /**
     * @param CollectionFactory       $collectionFactory
     * @param RetryQueueResource      $retryQueueResource
     * @param MessageInterfaceFactory $messageFactory
     * @param PublisherInterface      $publisher
     * @param Config                  $config
     * @param Json                    $serializer
     * @param LoggerInterface         $logger
     */
    public function __construct(
        private readonly CollectionFactory $collectionFactory,
        private readonly RetryQueueResource $retryQueueResource,
        private readonly MessageInterfaceFactory $messageFactory,
        private readonly PublisherInterface $publisher,
        private readonly Config $config,
        private readonly Json $serializer,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Executes the retry logic by publishing scheduled messages back to the queue.
     *
     * @return void
     */
    public function execute(): void
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('scheduled_at', ['lteq' => date('Y-m-d H:i:s')]);

        if ($collection->getSize() === 0) {
            return;
        }

        $this->logger->info((string)__('Processing %1 SMS retry attempts via Cron', $collection->getSize()));

        foreach ($collection as $retryEntry) {
            try {
                $payload = $this->serializer->unserialize($retryEntry->getData('message_payload'));

                $message = $this->messageFactory->create();
                $message->setMessage($payload['message']);
                $message->setPhone($payload['phone']);
                $message->setAttemptNumber((int)$payload['attempt_number']);
                $message->setStoreId((int)$payload['store_id']);

                $connection = $this->config->getQueueConnection((int)$payload['store_id']);
                $topic = $connection === 'amqp' ? 'muon.sms.amqp' : 'muon.sms';

                $this->publisher->publish($topic, $message);
                $this->retryQueueResource->delete($retryEntry);
            } catch (\Exception $e) {
                $this->logger->error(
                    (string)__('Error processing SMS retry ID %1: %2', $retryEntry->getId(), $e->getMessage())
                );
            }
        }
    }
}
