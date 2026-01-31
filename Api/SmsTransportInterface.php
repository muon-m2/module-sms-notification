<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Api;

/**
 * Interface for SMS transport mechanisms.
 * @api
 */
interface SmsTransportInterface
{
    /**
     * Sends a message to a specified phone number.
     *
     * @param string   $phone   The recipient's phone number.
     * @param string   $message The message content to be sent.
     * @param int|null $storeId
     *
     * @return void
     * @throws \Muon\SMSNotification\Exception\SmsTransportException
     */
    public function send(string $phone, string $message, ?int $storeId = null): void;
}
