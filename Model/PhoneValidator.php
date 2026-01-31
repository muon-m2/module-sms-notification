<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Model;

/**
 * Validator for E.164 phone number format.
 */
class PhoneValidator
{
    /**
     * Validates if a phone number is in E.164 format.
     *
     * @param string $phone
     * @return bool
     */
    public function isValid(string $phone): bool
    {
        return (bool)preg_match('/^\+[1-9]\d{0,14}$/', $phone);
    }
}
