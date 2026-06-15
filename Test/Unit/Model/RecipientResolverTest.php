<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Test\Unit\Model;

use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Muon\SMSNotification\Model\Config;
use Muon\SMSNotification\Model\RecipientResolver;
use PHPUnit\Framework\TestCase;

class RecipientResolverTest extends TestCase
{
    private $configMock;
    private $resolver;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->resolver = new RecipientResolver($this->configMock);
    }

    private function orderWithPhone(?string $telephone): OrderInterface
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getStoreId')->willReturn(1);

        if ($telephone !== null) {
            $address = $this->createMock(OrderAddressInterface::class);
            $address->method('getTelephone')->willReturn($telephone);
            $order->method('getBillingAddress')->willReturn($address);
        } else {
            $order->method('getBillingAddress')->willReturn(null);
        }

        return $order;
    }

    public function testAdminModeReturnsAdminPhone(): void
    {
        $this->configMock->method('getRecipientMode')->willReturn(RecipientResolver::MODE_ADMIN);
        $this->configMock->method('getSendToPhone')->willReturn('+14155550000');

        $this->assertSame('+14155550000', $this->resolver->resolveForOrder($this->orderWithPhone('+19998887777')));
    }

    public function testCustomerModeNormalisesCustomerPhone(): void
    {
        $this->configMock->method('getRecipientMode')->willReturn(RecipientResolver::MODE_CUSTOMER);

        $this->assertSame(
            '+4155551234',
            $this->resolver->resolveForOrder($this->orderWithPhone('(415) 555-1234'))
        );
    }

    public function testCustomerModeKeepsLeadingPlus(): void
    {
        $this->configMock->method('getRecipientMode')->willReturn(RecipientResolver::MODE_CUSTOMER);

        $this->assertSame(
            '+14155551234',
            $this->resolver->resolveForOrder($this->orderWithPhone('+1 415 555 1234'))
        );
    }

    public function testCustomerModeFallsBackToAdminWhenNoPhone(): void
    {
        $this->configMock->method('getRecipientMode')->willReturn(RecipientResolver::MODE_CUSTOMER);
        $this->configMock->method('getSendToPhone')->willReturn('+19998887777');

        $this->assertSame('+19998887777', $this->resolver->resolveForOrder($this->orderWithPhone('')));
    }
}
