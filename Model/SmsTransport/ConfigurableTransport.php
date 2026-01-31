<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Model\SmsTransport;

use Muon\SMSNotification\Model\Config;
use Muon\SMSNotification\Model\SmsTransportPool;
use Muon\SMSNotification\Api\SmsTransportInterface;

/**
 * Proxy transport that delegates the call to the configured transport service.
 */
class ConfigurableTransport implements SmsTransportInterface
{
    /**
     * Constructor
     *
     * @param Config           $config
     * @param SmsTransportPool $transportPool
     */
    public function __construct(
        private readonly Config $config,
        private readonly SmsTransportPool $transportPool
    ) {
    }

    /**
     * @inheritDoc
     */
    public function send(string $phone, string $message, ?int $storeId = null): void
    {
        $transportCode = $this->config->getTransportService($storeId);
        $transport = $this->transportPool->getTransport($transportCode);
        $transport->send($phone, $message, $storeId);
    }
}
