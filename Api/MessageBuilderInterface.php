<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Api;

/**
 * Interface defining methods for building messages.
 *
 * @api
 */
interface MessageBuilderInterface
{
    /**
     * Retrieves a message based on the provided data.
     *
     * @param mixed $data Input data used to generate the message.
     *
     * @return string The generated message.
     */
    public function getMessage(mixed $data): string;
}
