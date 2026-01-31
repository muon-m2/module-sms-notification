<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Model\SmsTransport;

use Psr\Log\LoggerInterface;
use Muon\SMSNotification\Api\SmsTransportInterface;

/**
 * SMS transport that logs the message instead of sending it.
 */
class Logger implements SmsTransportInterface
{
    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function send(string $phone, string $message, ?int $storeId = null): void
    {
        $this->logger->info('SMS notification (Logged Only)', [
            'phone' => $phone,
            'message' => $message,
            'store_id' => $storeId
        ]);
    }
}
