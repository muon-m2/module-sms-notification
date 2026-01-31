<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Provides options for SMS transport services.
 */
class Transport implements OptionSourceInterface
{
    /**
     * @var array|null
     */
    private ?array $options = null;

    /**
     * Constructor
     *
     * @param array $transports Available transports injected via di.xml
     */
    public function __construct(
        private readonly array $transports = []
    ) {
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        if ($this->options === null) {
            $this->options = [];
            foreach ($this->transports as $code => $label) {
                $this->options[] = [
                    'value' => $code,
                    'label' => __($label)
                ];
            }
        }
        return $this->options;
    }
}
