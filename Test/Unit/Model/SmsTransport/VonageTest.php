<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Test\Unit\Model\SmsTransport;

use Magento\Framework\HTTP\Client\Curl;
use Muon\SMSNotification\Exception\SmsTransportException;
use Muon\SMSNotification\Model\SmsTransport\Vonage;
use Muon\SMSNotification\Model\VonageConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class VonageTest extends TestCase
{
    private $configMock;
    private $curlMock;
    private $loggerMock;
    private $vonage;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(VonageConfig::class);
        $this->curlMock = $this->createMock(Curl::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->configMock->method('getApiKey')->willReturn('key');
        $this->configMock->method('getApiSecret')->willReturn('secret');
        $this->configMock->method('getFrom')->willReturn('Acme');

        $this->vonage = new Vonage($this->configMock, $this->curlMock, $this->loggerMock);
    }

    public function testSendSuccess(): void
    {
        $this->curlMock->expects($this->once())
            ->method('post')
            ->with('https://rest.nexmo.com/sms/json', $this->callback(static function (array $p): bool {
                return $p['to'] === '14155550000' && $p['api_key'] === 'key' && $p['text'] === 'Hi';
            }));
        $this->curlMock->method('getStatus')->willReturn(200);
        $this->curlMock->method('getBody')->willReturn('{"messages":[{"status":"0"}]}');
        $this->loggerMock->expects($this->once())->method('debug');

        $this->vonage->send('+14155550000', 'Hi', 1);
    }

    public function testSendThrowsOnApiErrorStatus(): void
    {
        $this->curlMock->method('getStatus')->willReturn(200);
        $this->curlMock->method('getBody')
            ->willReturn('{"messages":[{"status":"2","error-text":"Missing api_key"}]}');

        $this->expectException(SmsTransportException::class);
        $this->expectExceptionMessage('Missing api_key');

        $this->vonage->send('+14155550000', 'Hi', 1);
    }

    public function testSendThrowsOnHttpError(): void
    {
        $this->curlMock->method('getStatus')->willReturn(500);
        $this->curlMock->method('getBody')->willReturn('');

        $this->expectException(SmsTransportException::class);

        $this->vonage->send('+14155550000', 'Hi', 1);
    }
}
