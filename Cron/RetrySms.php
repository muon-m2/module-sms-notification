<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Cron;

use Muon\SMSNotification\Model\ResourceModel\RetryQueue\CollectionFactory;
use Muon\SMSNotification\Model\ResourceModel\RetryQueue as RetryQueueResource;
use Muon\SMSNotification\Model\RetryQueue;
use Muon\SMSNotification\Api\Data\MessageInterfaceFactory;
use Muon\SMSNotification\Model\Config;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

/**
 * Cron job for processing scheduled SMS retry attempts.
 *
 * Each run atomically claims a bounded batch of due rows (see RetryQueueResource::claimBatch)
 * so overlapping cron runs cannot pick up the same row and double-send.
 */
class RetrySms
{
    /**
     * Age (seconds) after which a row stuck in "processing" (crashed run) may be reclaimed.
     */
    private const STALE_LOCK_SECONDS = 600;

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
     * Claim a batch of due retries and republish them to the queue.
     *
     * @return void
     */
    public function execute(): void
    {
        $token = bin2hex(random_bytes(16));
        $claimed = $this->retryQueueResource->claimBatch(
            $token,
            $this->config->getRetryBatchSize(),
            self::STALE_LOCK_SECONDS
        );

        if ($claimed === 0) {
            return;
        }

        $this->logger->info((string)__('Processing %1 SMS retry attempts via Cron', $claimed));

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('claim_token', $token);

        foreach ($collection as $retryEntry) {
            try {
                $payload = $this->serializer->unserialize((string)$retryEntry->getData('message_payload'));

                $storeId = (int)$retryEntry->getData('store_id');
                if ($storeId === 0 && isset($payload['store_id'])) {
                    $storeId = (int)$payload['store_id'];
                }
                $attemptNumber = (int)$retryEntry->getData('attempt_number');
                if ($attemptNumber === 0 && isset($payload['attempt_number'])) {
                    $attemptNumber = (int)$payload['attempt_number'];
                }

                $message = $this->messageFactory->create();
                $message->setMessage((string)($payload['message'] ?? ''));
                $message->setPhone((string)($payload['phone'] ?? ''));
                $message->setAttemptNumber($attemptNumber);
                $message->setStoreId($storeId);

                $connection = $this->config->getQueueConnection($storeId);
                $topic = $connection === 'amqp' ? 'muon.sms.amqp' : 'muon.sms';

                $this->publisher->publish($topic, $message);
                $this->retryQueueResource->delete($retryEntry);
            } catch (\Throwable $e) {
                // Transient republish failure: release the row back for the next run and
                // record why, so it is not lost and stays visible in the grid.
                $retryEntry->setData('status', RetryQueue::STATUS_FAILED);
                $retryEntry->setData('last_error', $e->getMessage());
                $retryEntry->setData('claim_token', null);
                $retryEntry->setData('locked_at', null);
                $this->retryQueueResource->save($retryEntry);

                $this->logger->error(
                    (string)__('Error processing SMS retry ID %1: %2', $retryEntry->getId(), $e->getMessage())
                );
            }
        }
    }
}
