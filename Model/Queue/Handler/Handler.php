<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Model\Queue\Handler;

use Psr\Log\LoggerInterface;
use JetBrains\PhpStorm\ArrayShape;
use Muon\SMSNotification\Api\Data\MessageInterface;
use Muon\SMSNotification\Api\SmsTransportInterface;
use Muon\SMSNotification\Model\PhoneValidator;
use Muon\SMSNotification\Model\Queue\RetryHandler;
use Muon\SMSNotification\Exception\SmsTransportException;

class Handler
{
    public function __construct(
        private readonly SmsTransportInterface $smsTransport,
        private readonly RetryHandler $retryHandler,
        private readonly LoggerInterface $logger,
        private readonly PhoneValidator $phoneValidator
    ) {
    }

    public function execute(MessageInterface $message): void
    {
        try {
            if (!$this->phoneValidator->isValid($message->getPhone())) {
                throw new SmsTransportException((string)__('Invalid phone number format: %1', $message->getPhone()));
            }
            $this->smsTransport->send($message->getPhone(), $message->getMessage(), $message->getStoreId());
            $this->logger->info(
                'SMS sent',
                $this->getLoggerContext($message)
            );
        } catch (SmsTransportException $e) {
            $this->logger->error(
                sprintf('SMS transport error: %s', $e->getMessage()),
                $this->getLoggerContext($message)
            );
            $this->retryHandler->handle($message);
        }
    }

    /**
     * Retrieves the logging context for the provided message.
     *
     * @param MessageInterface $message The message instance containing the required data for the logger context.
     *
     * @return array An associative array containing the phone number, message content, and the number of attempts.
     */
    #[ArrayShape(['phone' => "string", 'message' => "string", 'attempts' => "int", 'store_id' => "int"])]
    private function getLoggerContext(MessageInterface $message): array
    {
        return [
            'phone'    => $message->getPhone(),
            'message'  => $message->getMessage(),
            'attempts' => $message->getAttemptNumber(),
            'store_id' => $message->getStoreId()
        ];
    }
}
