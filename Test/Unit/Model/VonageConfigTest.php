<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Test\Unit\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Muon\SMSNotification\Model\VonageConfig;
use PHPUnit\Framework\TestCase;

class VonageConfigTest extends TestCase
{
    private $scopeConfigMock;
    private $encryptorMock;
    private $config;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->encryptorMock = $this->createMock(EncryptorInterface::class);
        $this->config = new VonageConfig($this->scopeConfigMock, $this->encryptorMock);
    }

    public function testGetApiKeyDecryptsStoredValue(): void
    {
        $this->scopeConfigMock->method('getValue')
            ->with(VonageConfig::XML_API_KEY, 'store', 1)
            ->willReturn('encrypted-key');
        $this->encryptorMock->method('decrypt')->with('encrypted-key')->willReturn('plain-key');

        $this->assertSame('plain-key', $this->config->getApiKey(1));
    }

    public function testGetApiSecretDecryptsStoredValue(): void
    {
        $this->scopeConfigMock->method('getValue')
            ->with(VonageConfig::XML_API_SECRET, 'store', 1)
            ->willReturn('encrypted-secret');
        $this->encryptorMock->method('decrypt')->with('encrypted-secret')->willReturn('plain-secret');

        $this->assertSame('plain-secret', $this->config->getApiSecret(1));
    }

    public function testGetFromReturnsPlainValue(): void
    {
        $this->scopeConfigMock->method('getValue')
            ->with(VonageConfig::XML_FROM, 'store', null)
            ->willReturn('Acme');

        $this->assertSame('Acme', $this->config->getFrom());
    }
}
