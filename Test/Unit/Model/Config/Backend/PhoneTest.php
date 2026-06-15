<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Test\Unit\Model\Config\Backend;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Muon\SMSNotification\Model\Config\Backend\Phone;
use Muon\SMSNotification\Model\PhoneValidator;
use PHPUnit\Framework\TestCase;

class PhoneTest extends TestCase
{
    private Phone $model;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(Phone::class, [
            'phoneValidator' => new PhoneValidator(),
        ]);
    }

    public function testBeforeSaveThrowsOnInvalidPhone(): void
    {
        $this->expectException(LocalizedException::class);

        $this->model->setValue('not-a-phone');
        $this->model->beforeSave();
    }

    public function testBeforeSaveThrowsOnNonE164DigitsOnly(): void
    {
        $this->expectException(LocalizedException::class);

        // Missing the leading '+' — not valid E.164.
        $this->model->setValue('14155552671');
        $this->model->beforeSave();
    }
}
