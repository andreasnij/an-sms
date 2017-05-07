<?php

namespace Tests\Unit\Gateway;

use AnSms\Gateway\AbstractHttpGateway;
use PHPUnit\Framework\TestCase;

class AbstractHttpGatewayTest extends TestCase
{
    public function testThatAutoDiscoveryCreatesInstances()
    {
        $httpGateway = $this->getMockForAbstractClass(AbstractHttpGateway::class);
        /** @var AbstractHttpGateway $httpGateway */

        $this->assertNotEmpty($httpGateway->getHttpClient());
        $this->assertNotEmpty($httpGateway->getMessageFactory());
    }
}
