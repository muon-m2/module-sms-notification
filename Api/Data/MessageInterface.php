<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Api\Data;

/**
 * Interface MessageInterface
 *
 * Provides an abstraction for managing messages, phone numbers,
 * attempt numbers, and store identifiers.
 *
 * @package Muon\SMSNotification\Api\Data
 * @api
 */
interface MessageInterface
{
    public const  MESSAGE = 'message';
    public const  PHONE = 'phone';

    public const ATTEMPT_NUMBER = 'attempt_number';

    public const STORE_ID = 'store_id';

    /**
     * Retrieves a message as a string.
     *
     * @return string The message.
     */
    public function getMessage(): string;

    /**
     * Sets the message.
     *
     * @param string $message The message to set.
     *
     * @return \Muon\SMSNotification\Api\Data\MessageInterface
     */
    public function setMessage(string $message): self;

    /**
     * Retrieves the phone number.
     *
     * @return string The phone number.
     */
    public function getPhone(): string;

    /**
     * Sets the phone number.
     *
     * @param string $phone The phone number to set.
     *
     * @return \Muon\SMSNotification\Api\Data\MessageInterface
     */
    public function setPhone(string $phone): self;

    /**
     * Retrieves the attempt number.
     *
     * @return int The attempt number.
     */
    public function getAttemptNumber(): int;

    /**
     * Sets the attempt number.
     *
     * @param int $attemptNumber The number of attempts to set.
     *
     * @return \Muon\SMSNotification\Api\Data\MessageInterface
     */
    public function setAttemptNumber(int $attemptNumber): self;

    /**
     * Retrieves the store ID.
     *
     * @return int The store ID.
     */
    public function getStoreId(): int;

    /**
     * Sets the store ID.
     *
     * @param int $storeId The ID of the store to set.
     *
     * @return self The current instance for method chaining.
     */
    public function setStoreId(int $storeId): self;
}
