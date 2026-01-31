<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Model\ResourceModel\RetryQueue;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Muon\SMSNotification\Model\RetryQueue as Model;
use Muon\SMSNotification\Model\ResourceModel\RetryQueue as ResourceModel;

/**
 * RetryQueue collection.
 */
class Collection extends AbstractCollection
{
    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
