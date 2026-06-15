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
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_FAILED = 'failed';
    public const STATUS_DEAD = 'dead';

    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(ResourceModel::class);
    }
}
