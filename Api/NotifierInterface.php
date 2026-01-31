<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Api;

/**
 * Interface representing a notifier that is capable of sending messages such as SMS.
 *
 * @api
 */
interface NotifierInterface
{
    /**
     * Sends an SMS to the specified phone number with the given message.
     *
     * @param string   $phone   The recipient's phone number.
     * @param string   $message The message content to be sent.
     * @param int|null $storeId
     *
     * @return void
     */
    public function sendSMS(string $phone, string $message, ?int $storeId = null): void;
}
