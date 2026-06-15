<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Model;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{
    public const XML_SMS_NOTIFICATION_ENABLED = 'muon_sms_notification/general/enabled';
    public const XML_SMS_NOTIFICATION_NUMBER_ATTEMPTS = 'muon_sms_notification/general/number_attempts';
    public const XML_SMS_NOTIFICATION_RETRY_DELAY = 'muon_sms_notification/general/retry_delay';
    public const XML_SMS_NOTIFICATION_RETRY_BATCH_SIZE = 'muon_sms_notification/general/retry_batch_size';
    public const XML_SMS_NOTIFICATION_QUEUE_CONNECTION = 'muon_sms_notification/general/queue_connection';
    public const XML_SMS_NOTIFICATION_RATE_LIMIT = 'muon_sms_notification/general/rate_limit_per_minute';
    public const XML_SMS_NOTIFICATION_MAX_LENGTH = 'muon_sms_notification/general/max_length';

    public const DEFAULT_RETRY_BATCH_SIZE = 100;
    public const XML_SMS_NOTIFICATION_SEND_TO_PHONE = 'muon_sms_notification/general/send_to_phone';
    public const XML_SMS_NOTIFICATION_TRANSPORT_SERVICE = 'muon_sms_notification/general/transport_service';
    public const XML_SMS_NOTIFICATION_ORDER_ENABLED = 'muon_sms_notification/events/order_enabled';
    public const XML_SMS_NOTIFICATION_ORDER_TEMPLATE = 'muon_sms_notification/events/order_template';
    public const XML_SMS_NOTIFICATION_CUSTOMER_REGISTER_ENABLED = 'muon_sms_notification/events/customer_register_enabled';
    public const XML_SMS_NOTIFICATION_CUSTOMER_REGISTER_TEMPLATE = 'muon_sms_notification/events/customer_register_template';

    public function __construct(private readonly ScopeConfigInterface $scopeConfig) { }

    /**
     * Checks if the SMS notification feature is enabled for the specified store.
     *
     * @param int|null $storeId The ID of the store to check. If null, the default scope is used.
     *
     * @return bool True if the SMS notification feature is enabled, false otherwise.
     */
    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_SMS_NOTIFICATION_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Retrieves the number of attempts configured for SMS notifications.
     *
     * @param int|null $storeId The ID of the store scope. If null, the default scope is used.
     *
     * @return int The configured number of attempts.
     */
    public function getNumberAttempts(?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_SMS_NOTIFICATION_NUMBER_ATTEMPTS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Retrieves the retry delay (in seconds) for SMS notifications.
     *
     * @param int|null $storeId The ID of the store scope. If null, the default scope is used.
     *
     * @return int The configured retry delay in seconds.
     */
    public function getRetryDelay(?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_SMS_NOTIFICATION_RETRY_DELAY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Retrieves the maximum number of retry rows a single cron run may claim.
     *
     * @param int|null $storeId The ID of the store scope. If null, the default scope is used.
     *
     * @return int The configured batch size, falling back to a safe default when unset/invalid.
     */
    public function getRetryBatchSize(?int $storeId = null): int
    {
        $size = (int)$this->scopeConfig->getValue(
            self::XML_SMS_NOTIFICATION_RETRY_BATCH_SIZE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $size > 0 ? $size : self::DEFAULT_RETRY_BATCH_SIZE;
    }

    /**
     * Maximum SMS sends allowed per minute per store (0 = unlimited).
     *
     * @param int|null $storeId
     * @return int
     */
    public function getRateLimitPerMinute(?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_SMS_NOTIFICATION_RATE_LIMIT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Maximum outbound message length before truncation (0 = unlimited).
     *
     * @param int|null $storeId
     * @return int
     */
    public function getMaxLength(?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_SMS_NOTIFICATION_MAX_LENGTH,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Retrieves the configured queue connection (db or amqp).
     *
     * @param int|null $storeId The ID of the store scope. If null, the default scope is used.
     *
     * @return string The configured queue connection.
     */
    public function getQueueConnection(?int $storeId = null): string
    {
        return (string)($this->scopeConfig->getValue(
            self::XML_SMS_NOTIFICATION_QUEUE_CONNECTION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 'db');
    }

    /**
     * Retrieves the phone number configured for sending SMS notifications.
     *
     * @param int|null $storeId The ID of the store scope. If null, the default scope is used.
     *
     * @return string The configured phone number for SMS notifications.
     */
    public function getSendToPhone(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_SMS_NOTIFICATION_SEND_TO_PHONE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Retrieves the configured SMS transport service.
     *
     * @param int|null $storeId The ID of the store scope. If null, the default scope is used.
     *
     * @return string The code of the configured transport service.
     */
    public function getTransportService(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_SMS_NOTIFICATION_TRANSPORT_SERVICE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Retrieves the configured SMS template for order notifications.
     *
     * @param int|null $storeId The ID of the store scope. If null, the default scope is used.
     *
     * @return string The configured order notification template.
     */
    public function getOrderTemplate(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_SMS_NOTIFICATION_ORDER_TEMPLATE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Checks if the order SMS notification is enabled for the specified store.
     *
     * @param int|null $storeId The ID of the store to check. If null, the default scope is used.
     *
     * @return bool True if the order SMS notification is enabled, false otherwise.
     */
    public function isOrderEnabled(?int $storeId = null): bool
    {
        return $this->isEnabled($storeId) && $this->scopeConfig->isSetFlag(
            self::XML_SMS_NOTIFICATION_ORDER_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Checks if the customer registration SMS notification is enabled for the specified store.
     *
     * @param int|null $storeId The ID of the store to check. If null, the default scope is used.
     *
     * @return bool True if the customer registration SMS notification is enabled, false otherwise.
     */
    public function isCustomerRegisterEnabled(?int $storeId = null): bool
    {
        return $this->isEnabled($storeId) && $this->scopeConfig->isSetFlag(
            self::XML_SMS_NOTIFICATION_CUSTOMER_REGISTER_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Retrieves the configured SMS template for customer registration notifications.
     *
     * @param int|null $storeId The ID of the store scope. If null, the default scope is used.
     *
     * @return string The configured customer registration notification template.
     */
    public function getCustomerRegisterTemplate(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_SMS_NOTIFICATION_CUSTOMER_REGISTER_TEMPLATE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

}
