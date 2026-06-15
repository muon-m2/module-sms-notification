<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Model\SmsTransport;

use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;
use Muon\SMSNotification\Model\VonageConfig;
use Muon\SMSNotification\Api\SmsTransportInterface;
use Muon\SMSNotification\Exception\SmsTransportException;

/**
 * SMS transport that delivers messages via the Vonage (Nexmo) REST API.
 *
 * Uses Magento's HTTP client directly (no third-party SDK dependency). Any non-success
 * response is normalised to an SmsTransportException so the existing retry / dead-letter
 * pipeline handles Vonage failures the same way it handles Twilio failures.
 */
class Vonage implements SmsTransportInterface
{
    private const ENDPOINT = 'https://rest.nexmo.com/sms/json';

    /**
     * @param VonageConfig    $config
     * @param Curl            $curl
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly VonageConfig $config,
        private readonly Curl $curl,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function send(string $phone, string $message, ?int $storeId = null): void
    {
        $payload = [
            'api_key' => $this->config->getApiKey($storeId),
            'api_secret' => $this->config->getApiSecret($storeId),
            'from' => $this->config->getFrom($storeId),
            'to' => ltrim($phone, '+'),
            'text' => $message,
        ];

        try {
            $this->curl->post(self::ENDPOINT, $payload);
            $status = $this->curl->getStatus();
            $body = $this->curl->getBody();
        } catch (\Throwable $e) {
            throw new SmsTransportException('Vonage request failed: ' . $e->getMessage());
        }

        if ($status !== 200) {
            throw new SmsTransportException('Vonage HTTP error: ' . $status);
        }

        $data = json_decode($body, true);
        $messageStatus = $data['messages'][0]['status'] ?? null;

        if ($messageStatus !== '0') {
            $errorText = $data['messages'][0]['error-text'] ?? 'Unknown error';
            throw new SmsTransportException('Vonage send failed: ' . $errorText);
        }

        $this->logger->debug('SMS sent via Vonage');
    }
}
