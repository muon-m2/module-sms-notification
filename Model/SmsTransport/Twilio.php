<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Model\SmsTransport;

use Twilio\Rest\Client;
use Twilio\Rest\ClientFactory;
use Psr\Log\LoggerInterface;
use Twilio\Exceptions\TwilioException;
use Muon\SMSNotification\Model\TwilioConfig;
use Muon\SMSNotification\Api\SmsTransportInterface;
use Muon\SMSNotification\Exception\SmsTransportException;

/**
 * An implementation of SmsTransportInterface that handles sending SMS messages
 * using the Twilio service.
 */
class Twilio implements SmsTransportInterface
{
    /** @var \Twilio\Rest\Client[] $clients */
    private array $clients = [];

    public function __construct(
        private readonly TwilioConfig $config,
        private readonly ClientFactory $clientFactory,
        private readonly LoggerInterface $logger
    ) {
    }


    /**
     * @inheritDoc
     */
    public function send(string $phone, string $message, ?int $storeId = null): void
    {
        try {
            $response = $this->getClient((int)$storeId)->messages->create(
                $phone,
                [
                    'from' => $this->config->getSendFromPhone($storeId),
                    'body' => $message,
                ]
            );
        } catch (TwilioException $e) {
            $this->logger->error('SMS transport error: ' . $e->getMessage());
            throw new SmsTransportException('Twilio Exception: ' . $e->getMessage());
        }
        $this->logger->debug('SMS sent via Twilio', ['response' => (string)$response]);
    }

    /**
     * Retrieves an instance of the Client to interact with the service.
     *
     * @return \Twilio\Rest\Client The Client instance configured with account credentials.
     */
    private function getClient(int $storeId): Client
    {
        if (!isset($this->clients[$storeId])) {
            $this->clients[$storeId] = $this->clientFactory->create(
                [
                    'username' => $this->config->getAccountSid($storeId),
                    'password' => $this->config->getAuthToken($storeId)
                ]
            );
        }

        return $this->clients[$storeId];
    }
}
