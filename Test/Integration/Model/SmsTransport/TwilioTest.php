<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Test\Integration\Model\SmsTransport;

use Magento\TestFramework\Helper\Bootstrap;
use Muon\SMSNotification\Model\SmsTransport\Twilio;
use Twilio\Rest\ClientFactory;
use Twilio\Rest\Client;
use Twilio\Rest\Api\V2010\Account\MessageList;
use PHPUnit\Framework\TestCase;

class TwilioTest extends TestCase
{
    private $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * @magentoConfigFixture default_store muon_sms_notification/transport/twilio_sid encrypted_sid
     * @magentoConfigFixture default_store muon_sms_notification/transport/twilio_token encrypted_token
     * @magentoConfigFixture default_store muon_sms_notification/transport/twilio_send_from_phone +15550001111
     */
    public function testSendUsesCorrectConfigAndCallsTwilioClient(): void
    {
        // Mocking the Twilio Client and its messages property
        $clientMock = $this->createMock(Client::class);
        $messageListMock = $this->createMock(MessageList::class);

        $clientMock->messages = $messageListMock;

        $messageListMock->expects($this->once())
            ->method('create')
            ->with(
                '+1234567890',
                [
                    'from' => '+15550001111',
                    'body' => 'Test message',
                ]
            );

        // Mocking ClientFactory
        $clientFactoryMock = $this->createMock(ClientFactory::class);
        $clientFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($clientMock);

        // Inject the mocked factory
        $twilioTransport = $this->objectManager->create(Twilio::class, [
            'clientFactory' => $clientFactoryMock
        ]);

        $twilioTransport->send('+1234567890', 'Test message');
    }
}
