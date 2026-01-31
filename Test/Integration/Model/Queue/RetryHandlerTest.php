<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Test\Integration\Model\Queue;

use PHPUnit\Framework\TestCase;
use Muon\SMSNotification\Model\Queue\RetryHandler;
use Muon\SMSNotification\Model\Queue\Handler\Handler;
use Magento\TestFramework\Helper\Bootstrap;

class RetryHandlerTest extends TestCase
{
    private $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    public function testRetryHandlerIsInstantiable(): void
    {
        $retryHandler = $this->objectManager->create(RetryHandler::class);
        $this->assertInstanceOf(RetryHandler::class, $retryHandler);
    }

    public function testHandlerIsInstantiable(): void
    {
        $handler = $this->objectManager->create(Handler::class);
        $this->assertInstanceOf(Handler::class, $handler);
    }
}
