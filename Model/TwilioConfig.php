<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Model;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * TwilioConfig provides configuration management for Twilio SMS settings.
 */
class TwilioConfig
{
    public const XML_SMS_ACCOUNT_SID = 'muon_sms_notification/transport/twilio_sid';
    public const XML_SMS_AUTH_TOKEN = 'muon_sms_notification/transport/twilio_token';
    public const XML_SMS_SEND_FROM_PHONE = 'muon_sms_notification/transport/twilio_send_from_phone';

    /**
     * Constructor method.
     *
     * @param ScopeConfigInterface $scopeConfig Configuration scope object.
     * @param EncryptorInterface   $encryptor   Encryption interface instance.
     *
     * @return void
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly EncryptorInterface $encryptor
    ) {
    }

    /**
     * Retrieves the Account SID configuration value for the specified store ID.
     *
     * @param int|null $storeId Store ID for which to retrieve the Account SID.
     *
     * @return string The decrypted Account SID.
     */
    public function getAccountSid(?int $storeId = null): string
    {
        $encrypted = (string)$this->scopeConfig->getValue(
            self::XML_SMS_ACCOUNT_SID,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $this->encryptor->decrypt($encrypted);
    }

    /**
     * Retrieves the authorization token for a specified store.
     *
     * @param int|null $storeId The ID of the store for which to fetch the token.
     *
     * @return string The decrypted authorization token.
     */
    public function getAuthToken(?int $storeId = null): string
    {
        $encrypted = (string)$this->scopeConfig->getValue(
            self::XML_SMS_AUTH_TOKEN,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $this->encryptor->decrypt($encrypted);
    }

    /**
     * Retrieves the phone number used for sending SMS messages for a specific store.
     *
     * @param int|null $storeId The ID of the store to retrieve the phone number for.
     *
     * @return string The phone number configured for sending SMS messages.
     */
    public function getSendFromPhone(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_SMS_SEND_FROM_PHONE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
