<?php

namespace AnSms\Tests\Gateway;

use AnSms\Gateway\AbstractHttpGateway;
use PHPUnit\Framework\TestCase;

class AbstractHttpGatewayTest extends TestCase
{
    public function testThatAutoDiscoveryCreatesInstances(): void
    {
        $httpGateway = new class extends AbstractHttpGateway {}; // @phpcs:ignore

        $this->assertNotEmpty($httpGateway->getHttpClient());
        $this->assertNotEmpty($httpGateway->getRequestFactory());
        $this->assertNotEmpty($httpGateway->getStreamFactory());
    }
}
