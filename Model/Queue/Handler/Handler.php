<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Model\Queue\Handler;

use Psr\Log\LoggerInterface;
use JetBrains\PhpStorm\ArrayShape;
use Muon\SMSNotification\Api\Data\MessageInterface;
use Muon\SMSNotification\Api\SmsTransportInterface;
use Muon\SMSNotification\Model\PhoneValidator;
use Muon\SMSNotification\Model\Queue\RetryHandler;
use Muon\SMSNotification\Model\RateLimiter;

class Handler
{
    /**
     * Delay (seconds) a rate-limited message is deferred before re-attempting.
     */
    private const RATE_LIMIT_DEFER_SECONDS = 60;

    public function __construct(
        private readonly SmsTransportInterface $smsTransport,
        private readonly RetryHandler $retryHandler,
        private readonly LoggerInterface $logger,
        private readonly PhoneValidator $phoneValidator,
        private readonly RateLimiter $rateLimiter
    ) {
    }

    public function execute(MessageInterface $message): void
    {
        // An invalid phone number is a permanent failure: it will never become valid on
        // retry, so drop it instead of consuming the whole retry budget.
        if (!$this->phoneValidator->isValid($message->getPhone())) {
            $this->logger->warning(
                'SMS dropped: invalid phone number format',
                $this->getLoggerContext($message)
            );
            return;
        }

        // Rate limiting is not a failure: defer without consuming the retry budget.
        if (!$this->rateLimiter->tryAcquire($message->getStoreId())) {
            $this->logger->info('SMS rate limited; deferring', $this->getLoggerContext($message));
            $this->retryHandler->defer($message, self::RATE_LIMIT_DEFER_SECONDS);
            return;
        }

        try {
            $this->smsTransport->send($message->getPhone(), $message->getMessage(), $message->getStoreId());
            $this->logger->info(
                'SMS sent',
                $this->getLoggerContext($message)
            );
        } catch (\Throwable $e) {
            // Catch any throwable (transport error, SDK/network error, misconfiguration)
            // so the consumer never dies on an unexpected exception; route to retry.
            $this->logger->error(
                sprintf('SMS transport error: %s', $e->getMessage()),
                $this->getLoggerContext($message)
            );
            $this->retryHandler->handle($message, $e->getMessage());
        }
    }

    /**
     * Retrieves the logging context for the provided message.
     *
     * The recipient phone is masked and the message body is intentionally omitted to keep
     * PII (phone numbers, customer-derived content) out of the system logs.
     *
     * @param MessageInterface $message The message instance containing the required data for the logger context.
     *
     * @return array An associative array containing the masked phone, attempt number, and store id.
     */
    #[ArrayShape(['phone' => "string", 'attempts' => "int", 'store_id' => "int"])]
    private function getLoggerContext(MessageInterface $message): array
    {
        return [
            'phone'    => $this->maskPhone($message->getPhone()),
            'attempts' => $message->getAttemptNumber(),
            'store_id' => $message->getStoreId()
        ];
    }

    /**
     * Masks a phone number for logging, keeping only the last four characters.
     *
     * @param string $phone
     * @return string
     */
    private function maskPhone(string $phone): string
    {
        $length = strlen($phone);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return str_repeat('*', $length - 4) . substr($phone, -4);
    }
}
