<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Model;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * VonageConfig provides configuration management for Vonage (Nexmo) SMS settings.
 */
class VonageConfig
{
    public const XML_API_KEY = 'muon_sms_notification/vonage/api_key';
    public const XML_API_SECRET = 'muon_sms_notification/vonage/api_secret';
    public const XML_FROM = 'muon_sms_notification/vonage/from';

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param EncryptorInterface   $encryptor
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly EncryptorInterface $encryptor
    ) {
    }

    /**
     * @param int|null $storeId
     * @return string The decrypted API key.
     */
    public function getApiKey(?int $storeId = null): string
    {
        return $this->encryptor->decrypt((string)$this->scopeConfig->getValue(
            self::XML_API_KEY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
    }

    /**
     * @param int|null $storeId
     * @return string The decrypted API secret.
     */
    public function getApiSecret(?int $storeId = null): string
    {
        return $this->encryptor->decrypt((string)$this->scopeConfig->getValue(
            self::XML_API_SECRET,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
    }

    /**
     * @param int|null $storeId
     * @return string The sender id / number used for outbound messages.
     */
    public function getFrom(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_FROM,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
