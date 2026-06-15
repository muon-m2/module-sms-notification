<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Model;

use Magento\Framework\App\CacheInterface;

/**
 * Per-store fixed-window send-rate limiter backed by the application cache.
 */
class RateLimiter
{
    private const CACHE_PREFIX = 'muon_sms_rate_';
    private const CACHE_TAG = 'MUON_SMS_RATE';
    private const WINDOW_TTL = 120;

    /**
     * @param CacheInterface $cache
     * @param Config         $config
     */
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly Config $config
    ) {
    }

    /**
     * Attempt to consume one unit of the current minute's send budget.
     *
     * @param int|null $storeId
     * @return bool True if the send is within the configured limit (0 = unlimited).
     */
    public function tryAcquire(?int $storeId = null): bool
    {
        $limit = $this->config->getRateLimitPerMinute($storeId);
        if ($limit <= 0) {
            return true;
        }

        $key = self::CACHE_PREFIX . (int)$storeId . '_' . gmdate('YmdHi');
        $current = (int)$this->cache->load($key);

        if ($current >= $limit) {
            return false;
        }

        $this->cache->save((string)($current + 1), $key, [self::CACHE_TAG], self::WINDOW_TTL);

        return true;
    }
}
