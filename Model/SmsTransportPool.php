<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Model;

use Muon\SMSNotification\Api\SmsTransportInterface;

/**
 * Pool of SMS transport implementations.
 */
class SmsTransportPool
{
    /**
     * Constructor
     *
     * @param SmsTransportInterface[] $transports
     */
    public function __construct(
        private readonly array $transports = []
    ) {
    }

    /**
     * Retrieves a transport implementation by its code.
     *
     * @param string $code
     * @return SmsTransportInterface
     * @throws \InvalidArgumentException If the transport code is not found.
     */
    public function getTransport(string $code): SmsTransportInterface
    {
        if (!isset($this->transports[$code])) {
            throw new \InvalidArgumentException(sprintf('SMS transport with code "%s" not found.', $code));
        }

        return $this->transports[$code];
    }
}
