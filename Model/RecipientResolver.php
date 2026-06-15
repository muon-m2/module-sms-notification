<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Model;

use Magento\Sales\Api\Data\OrderInterface;

/**
 * Resolves the SMS recipient for an order: the admin number or the customer's own phone,
 * depending on the configured recipient mode.
 */
class RecipientResolver
{
    public const MODE_ADMIN = 'admin';
    public const MODE_CUSTOMER = 'customer';

    /**
     * @param Config $config
     */
    public function __construct(private readonly Config $config)
    {
    }

    /**
     * @param OrderInterface $order
     * @return string The resolved recipient phone number.
     */
    public function resolveForOrder(OrderInterface $order): string
    {
        $storeId = (int)$order->getStoreId();

        if ($this->config->getRecipientMode($storeId) === self::MODE_CUSTOMER) {
            $address = $order->getBillingAddress();
            $customerPhone = $this->normalize($address ? (string)$address->getTelephone() : '');
            if ($customerPhone !== '') {
                return $customerPhone;
            }
        }

        return $this->config->getSendToPhone($storeId);
    }

    /**
     * Best-effort normalisation of a free-form phone number toward E.164.
     *
     * @param string $phone
     * @return string
     */
    private function normalize(string $phone): string
    {
        $clean = preg_replace('/[^\d+]/', '', trim($phone)) ?? '';
        if ($clean === '') {
            return '';
        }
        if (str_starts_with($clean, '+')) {
            return $clean;
        }
        if (str_starts_with($clean, '00')) {
            return '+' . substr($clean, 2);
        }

        return '+' . ltrim($clean, '0');
    }
}
