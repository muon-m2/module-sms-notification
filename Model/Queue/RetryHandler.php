<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Model\Queue;

use Psr\Log\LoggerInterface;
use Muon\SMSNotification\Model\Config;
use Magento\Framework\Serialize\Serializer\Json;
use Muon\SMSNotification\Model\ResourceModel\RetryQueue as RetryQueueResource;
use Muon\SMSNotification\Model\RetryQueueFactory;
use Muon\SMSNotification\Api\Data\MessageInterface;

/**
 * Handles the retry logic for SMS notifications using a Cron-based system.
 */
class RetryHandler
{
    /**
     * @param Config $config
     * @param RetryQueueFactory $retryQueueFactory
     * @param RetryQueueResource $retryQueueResource
     * @param Json $serializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly Config $config,
        private readonly RetryQueueFactory $retryQueueFactory,
        private readonly RetryQueueResource $retryQueueResource,
        private readonly Json $serializer,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Handles the retry of a message by scheduling it in the database.
     *
     * @param MessageInterface $message
     * @return void
     */
    public function handle(MessageInterface $message): void
    {
        $attempts = $message->getAttemptNumber();
        $storeId = $message->getStoreId();
        $maxAttempts = $this->config->getNumberAttempts($storeId);

        if ($attempts < $maxAttempts) {
            $delay = $this->config->getRetryDelay($storeId);
            $scheduledAt = time() + $delay;

            $message->setAttemptNumber($attempts + 1);

            $retryEntry = $this->retryQueueFactory->create();
            $retryEntry->setData([
                'message_payload' => $this->serializer->serialize([
                    'message' => $message->getMessage(),
                    'phone' => $message->getPhone(),
                    'attempt_number' => $message->getAttemptNumber(),
                    'store_id' => $message->getStoreId(),
                ]),
                'scheduled_at' => date('Y-m-d H:i:s', $scheduledAt)
            ]);
            $this->retryQueueResource->save($retryEntry);

            $this->logger->info(
                (string)__('SMS scheduled for retry via Cron (Attempt %1 of %2, Scheduled at %3)',
                    $message->getAttemptNumber(),
                    $maxAttempts,
                    date('Y-m-d H:i:s', $scheduledAt)
                ),
                ['phone' => $message->getPhone()]
            );
        } else {
            $this->logger->critical(
                (string)__('SMS sending attempts exhausted for %1', $message->getPhone())
            );
        }
    }
}
