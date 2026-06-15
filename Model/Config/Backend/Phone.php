<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Model\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Muon\SMSNotification\Model\PhoneValidator;

/**
 * Backend model that validates a configured phone number is in E.164 format before saving.
 */
class Phone extends Value
{
    /**
     * @param Context                $context
     * @param Registry               $registry
     * @param ScopeConfigInterface   $config
     * @param TypeListInterface      $cacheTypeList
     * @param PhoneValidator         $phoneValidator
     * @param AbstractResource|null  $resource
     * @param AbstractDb|null        $resourceCollection
     * @param array                  $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        private readonly PhoneValidator $phoneValidator,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Validate the phone number format before persisting the configuration value.
     *
     * @return $this
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        $value = trim((string)$this->getValue());

        if ($value !== '' && !$this->phoneValidator->isValid($value)) {
            throw new LocalizedException(
                __('Please enter the phone number in E.164 format, for example +14155552671.')
            );
        }

        return parent::beforeSave();
    }
}
