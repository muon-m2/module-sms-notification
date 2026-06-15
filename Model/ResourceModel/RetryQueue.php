<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Muon\SMSNotification\Model\RetryQueue as RetryQueueModel;

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

    /**
     * Atomically claim a batch of due rows for a single cron run.
     *
     * A single UPDATE flips eligible rows to "processing" and stamps them with this run's
     * claim token. Because the UPDATE is atomic and filters on status, two overlapping cron
     * runs can never claim the same row, which prevents duplicate SMS sends. Rows left in
     * "processing" by a crashed run are reclaimed once they exceed the stale window.
     *
     * @param string $token       Unique token identifying this cron run.
     * @param int    $limit       Maximum number of rows to claim.
     * @param int    $staleSeconds Age after which a stuck "processing" row may be reclaimed.
     * @return int Number of rows claimed.
     */
    public function claimBatch(string $token, int $limit, int $staleSeconds): int
    {
        $limit = max(1, $limit);
        $staleSeconds = max(1, $staleSeconds);
        $connection = $this->getConnection();
        $table = $connection->quoteIdentifier($this->getMainTable());

        $sql = sprintf(
            'UPDATE %s SET status = ?, claim_token = ?, locked_at = NOW()'
            . ' WHERE ((status IN (?, ?) AND scheduled_at <= NOW())'
            . ' OR (status = ? AND locked_at IS NOT NULL AND locked_at < (NOW() - INTERVAL %d SECOND)))'
            . ' ORDER BY scheduled_at ASC LIMIT %d',
            $table,
            $staleSeconds,
            $limit
        );

        $result = $connection->query(
            $sql,
            [
                RetryQueueModel::STATUS_PROCESSING,
                $token,
                RetryQueueModel::STATUS_PENDING,
                RetryQueueModel::STATUS_FAILED,
                RetryQueueModel::STATUS_PROCESSING,
            ]
        );

        return $result->rowCount();
    }
}
