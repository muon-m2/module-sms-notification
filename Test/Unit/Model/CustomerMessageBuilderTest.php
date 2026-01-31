<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Muon\SMSNotification\Model\CustomerMessageBuilder;
use Muon\SMSNotification\Model\Config;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\Data\StoreInterface;

class CustomerMessageBuilderTest extends TestCase
{
    private $configMock;
    private $storeManagerMock;
    private $builder;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->builder = new CustomerMessageBuilder($this->configMock, $this->storeManagerMock);
    }

    public function testGetMessage(): void
    {
        $customerMock = $this->createMock(CustomerInterface::class);
        $customerMock->method('getStoreId')->willReturn(1);
        $customerMock->method('getFirstname')->willReturn('John');
        $customerMock->method('getLastname')->willReturn('Doe');
        $customerMock->method('getEmail')->willReturn('john.doe@example.com');

        $storeMock = $this->createMock(StoreInterface::class);
        $storeMock->method('getName')->willReturn('Main Store');
        $this->storeManagerMock->method('getStore')->with(1)->willReturn($storeMock);

        $this->configMock->method('getCustomerRegisterTemplate')->willReturn(
            'New customer: {{customer_name}} ({{customer_email}}). Store: {{store_name}}'
        );

        $expectedMessage = 'New customer: John Doe (john.doe@example.com). Store: Main Store';

        $this->assertEquals($expectedMessage, $this->builder->getMessage($customerMock));
    }

    public function testGetMessageInvalidData(): void
    {
        $this->assertEquals('Customer data is not valid', $this->builder->getMessage(null));
    }
}
