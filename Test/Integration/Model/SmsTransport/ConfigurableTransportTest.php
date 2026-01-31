<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Test\Integration\Model\SmsTransport;

use Magento\TestFramework\Helper\Bootstrap;
use Muon\SMSNotification\Model\SmsTransport\ConfigurableTransport;
use Muon\SMSNotification\Model\SmsTransport\Logger as LoggerTransport;
use Muon\SMSNotification\Model\SmsTransport\Twilio as TwilioTransport;
use PHPUnit\Framework\TestCase;

class ConfigurableTransportTest extends TestCase
{
    private $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * @magentoConfigFixture default_store muon_sms_notification/general/transport_service logger
     */
    public function testSendDelegatesToLoggerTransport(): void
    {
        // Mock LoggerTransport to verify it's called
        $loggerTransportMock = $this->createMock(LoggerTransport::class);
        $loggerTransportMock->expects($this->once())
            ->method('send')
            ->with('+1234567890', 'Test message', null);

        // Map the mock in the ObjectManager for the pool to pick it up
        // Note: SmsTransportPool is injected into ConfigurableTransport.
        // In integration tests, we can use the object manager to configure arguments if needed,
        // but here it's easier to just mock the transport instance if we can inject it.
        // However, SmsTransportPool receives an array of transports.

        $this->objectManager->addSharedInstance($loggerTransportMock, LoggerTransport::class);

        /** @var ConfigurableTransport $configurableTransport */
        $configurableTransport = $this->objectManager->get(ConfigurableTransport::class);
        $configurableTransport->send('+1234567890', 'Test message');
    }

    /**
     * @magentoConfigFixture default_store muon_sms_notification/general/transport_service twilio
     */
    public function testSendDelegatesToTwilioTransport(): void
    {
        $twilioTransportMock = $this->createMock(TwilioTransport::class);
        $twilioTransportMock->expects($this->once())
            ->method('send')
            ->with('+1234567890', 'Test message', null);

        $this->objectManager->addSharedInstance($twilioTransportMock, TwilioTransport::class);

        /** @var ConfigurableTransport $configurableTransport */
        $configurableTransport = $this->objectManager->get(ConfigurableTransport::class);
        $configurableTransport->send('+1234567890', 'Test message');
    }
}
