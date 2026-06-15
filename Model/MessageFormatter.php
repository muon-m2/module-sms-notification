<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Model;

/**
 * Applies length limits to outbound SMS text and estimates carrier segment counts.
 */
class MessageFormatter
{
    private const GSM_SINGLE = 160;
    private const GSM_MULTI = 153;
    private const UCS2_SINGLE = 70;
    private const UCS2_MULTI = 67;
    private const ELLIPSIS = '…';

    /**
     * @param Config $config
     */
    public function __construct(private readonly Config $config)
    {
    }

    /**
     * Truncate the message to the configured maximum length (0 = unlimited).
     *
     * @param string   $text
     * @param int|null $storeId
     * @return string
     */
    public function format(string $text, ?int $storeId = null): string
    {
        $max = $this->config->getMaxLength($storeId);
        if ($max <= 0 || mb_strlen($text) <= $max) {
            return $text;
        }

        $cut = $max - mb_strlen(self::ELLIPSIS);
        if ($cut < 1) {
            return mb_substr($text, 0, $max);
        }

        return mb_substr($text, 0, $cut) . self::ELLIPSIS;
    }

    /**
     * Estimate the number of SMS segments the carrier will bill for the text.
     *
     * @param string $text
     * @return int
     */
    public function segmentCount(string $text): int
    {
        $length = mb_strlen($text);
        if ($length === 0) {
            return 0;
        }

        $unicode = (bool)preg_match('/[^\x00-\x7F]/', $text);
        $single = $unicode ? self::UCS2_SINGLE : self::GSM_SINGLE;
        $multi = $unicode ? self::UCS2_MULTI : self::GSM_MULTI;

        return $length <= $single ? 1 : (int)ceil($length / $multi);
    }
}
