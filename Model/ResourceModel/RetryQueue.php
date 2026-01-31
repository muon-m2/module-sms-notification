<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * RetryQueue resource model.
 */
class RetryQueue extends AbstractDb
{
    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init('muon_sms_retry_queue', 'entity_id');
    }
}
