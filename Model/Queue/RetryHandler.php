<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Model\Queue;

use Psr\Log\LoggerInterface;
use Muon\SMSNotification\Model\Config;
use Magento\Framework\Serialize\Serializer\Json;
use Muon\SMSNotification\Model\ResourceModel\RetryQueue as RetryQueueResource;
use Muon\SMSNotification\Model\RetryQueue;
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
     * Schedules a message for retry, or moves it to the dead-letter state when exhausted.
     *
     * @param MessageInterface $message
     * @param string|null $error The error that triggered the retry (stored for diagnostics).
     * @return void
     */
    public function handle(MessageInterface $message, ?string $error = null): void
    {
        $attempts = $message->getAttemptNumber();
        $storeId = $message->getStoreId();
        $maxAttempts = $this->config->getNumberAttempts($storeId);

        if ($attempts < $maxAttempts) {
            $delay = $this->config->getRetryDelay($storeId);
            $scheduledAt = time() + $delay;
            $message->setAttemptNumber($attempts + 1);

            $this->persist(
                $message,
                RetryQueue::STATUS_PENDING,
                $error,
                date('Y-m-d H:i:s', $scheduledAt)
            );

            $this->logger->info(
                (string)__(
                    'SMS scheduled for retry via Cron (Attempt %1 of %2, Scheduled at %3)',
                    $message->getAttemptNumber(),
                    $maxAttempts,
                    date('Y-m-d H:i:s', $scheduledAt)
                ),
                ['store_id' => $storeId]
            );

            return;
        }

        // Attempts exhausted: preserve the message in the dead-letter state for inspection
        // and manual replay instead of silently dropping it.
        $this->persist($message, RetryQueue::STATUS_DEAD, $error, date('Y-m-d H:i:s'));

        $this->logger->critical(
            (string)__('SMS sending attempts exhausted; message moved to dead-letter (attempts: %1)', $attempts),
            ['store_id' => $storeId]
        );
    }

    /**
     * Persist a retry-queue row with the given status.
     *
     * @param MessageInterface $message
     * @param string $status
     * @param string|null $error
     * @param string $scheduledAt
     * @return void
     */
    private function persist(MessageInterface $message, string $status, ?string $error, string $scheduledAt): void
    {
        $retryEntry = $this->retryQueueFactory->create();
        $retryEntry->setData([
            'status' => $status,
            'attempt_number' => $message->getAttemptNumber(),
            'store_id' => (int)$message->getStoreId(),
            'last_error' => $error,
            'message_payload' => $this->serializer->serialize([
                'message' => $message->getMessage(),
                'phone' => $message->getPhone(),
                'attempt_number' => $message->getAttemptNumber(),
                'store_id' => $message->getStoreId(),
            ]),
            'scheduled_at' => $scheduledAt,
        ]);
        $this->retryQueueResource->save($retryEntry);
    }
}
