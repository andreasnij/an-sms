<?php

namespace AnSms\Tests\Gateway;

use AnSms\Gateway\NullGateway;
use AnSms\Message\DeliveryReport\DeliveryReportInterface;
use AnSms\Message\MessageInterface;
use PHPUnit\Framework\TestCase;

class NullGatewayTest extends TestCase
{
    /**
     * @var NullGateway
     */
    private $gateway;

    protected function setUp(): void
    {
        $this->gateway = new NullGateway();
    }

    public function testReceiveMessage(): void
    {
        $message = $this->gateway->receiveMessage([]);

        $this->assertInstanceOf(MessageInterface::class, $message);
    }

    public function testReceiveDeliveryReport(): void
    {
        $deliveryReport = $this->gateway->receiveDeliveryReport([]);

        $this->assertInstanceOf(DeliveryReportInterface::class, $deliveryReport);
    }
}
