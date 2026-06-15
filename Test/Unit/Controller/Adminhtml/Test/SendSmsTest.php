<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Test\Unit\Controller\Adminhtml\Test;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Muon\SMSNotification\Api\SmsTransportInterface;
use Muon\SMSNotification\Controller\Adminhtml\Test\SendSms;
use Muon\SMSNotification\Model\Config;
use PHPUnit\Framework\TestCase;

class SendSmsTest extends TestCase
{
    private $jsonResultMock;
    private $transportMock;
    private $configMock;
    private $controller;

    protected function setUp(): void
    {
        $this->jsonResultMock = $this->createMock(Json::class);
        $this->jsonResultMock->method('setData')->willReturnSelf();

        $jsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $jsonFactory->method('create')->willReturn($this->jsonResultMock);

        $this->transportMock = $this->createMock(SmsTransportInterface::class);
        $this->configMock = $this->createMock(Config::class);

        $this->controller = new SendSms(
            $this->createMock(Context::class),
            $jsonFactory,
            $this->transportMock,
            $this->configMock
        );
    }

    public function testExecuteSendsTestMessageThroughConfiguredTransport(): void
    {
        $this->configMock->method('getSendToPhone')->willReturn('+14155550000');
        $this->transportMock->expects($this->once())
            ->method('send')
            ->with('+14155550000', $this->isType('string'), null);

        $this->jsonResultMock->expects($this->once())
            ->method('setData')
            ->with($this->callback(static fn (array $d): bool => $d['success'] === true))
            ->willReturnSelf();

        $this->assertSame($this->jsonResultMock, $this->controller->execute());
    }

    public function testExecuteReturnsErrorWhenNoRecipientConfigured(): void
    {
        $this->configMock->method('getSendToPhone')->willReturn('');
        $this->transportMock->expects($this->never())->method('send');

        $this->jsonResultMock->expects($this->once())
            ->method('setData')
            ->with($this->callback(static fn (array $d): bool => $d['success'] === false))
            ->willReturnSelf();

        $this->controller->execute();
    }

    public function testExecuteReturnsErrorWhenTransportThrows(): void
    {
        $this->configMock->method('getSendToPhone')->willReturn('+14155550000');
        $this->transportMock->method('send')->willThrowException(new \RuntimeException('boom'));

        $this->jsonResultMock->expects($this->once())
            ->method('setData')
            ->with($this->callback(static fn (array $d): bool => $d['success'] === false && $d['message'] === 'boom'))
            ->willReturnSelf();

        $this->controller->execute();
    }
}
