<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Model;

use Magento\Framework\Model\AbstractModel;
use Muon\SMSNotification\Model\ResourceModel\RetryQueue as ResourceModel;

/**
 * RetryQueue model.
 */
class RetryQueue extends AbstractModel
{
    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(ResourceModel::class);
    }
}
