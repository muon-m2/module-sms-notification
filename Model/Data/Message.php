<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Model\Data;

use Magento\Framework\DataObject;
use Muon\SMSNotification\Api\Data\MessageInterface;

class Message extends DataObject implements MessageInterface
{

    /**
     * @inheritDoc
     */
    public function getMessage(): string
    {
        return (string)$this->getData(self::MESSAGE);
    }

    /**
     * @inheritDoc
     */
    public function setMessage(string $message): MessageInterface
    {
        return $this->setData(self::MESSAGE, $message);
    }

    /**
     * @inheritDoc
     */
    public function getPhone(): string
    {
        return (string)$this->getData(self::PHONE);
    }

    /**
     * @inheritDoc
     */
    public function setPhone(string $phone): MessageInterface
    {
        return $this->setData(self::PHONE, $phone);
    }

    /**
     * @inheritDoc
     */
    public function getAttemptNumber(): int
    {
        return (int)$this->getData(self::ATTEMPT_NUMBER);
    }

    /**
     * @inheritDoc
     */
    public function setAttemptNumber(int $attemptNumber): MessageInterface
    {
        return $this->setData(self::ATTEMPT_NUMBER, $attemptNumber);
    }

    /** @inheritDoc */
    public function getStoreId(): int
    {
        return (int)$this->getData(self::STORE_ID);
    }

    /** @inheritDoc*/
    public function setStoreId(int $storeId): MessageInterface
    {
        return $this->setData(self::STORE_ID, $storeId);
    }
}
