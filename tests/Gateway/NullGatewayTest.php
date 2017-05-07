<?php

namespace Tests\Gateway;

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

    protected function setUp()
    {
        $this->gateway = new NullGateway();
    }

    public function testSendMessage()
    {
        $messageMock = $this->createMock(MessageInterface::class);
        $messageMock->expects($this->once())->method('setId');

        $this->gateway->sendMessage($messageMock);
    }

    public function testSendMessages()
    {
        $messageMock = $this->createMock(MessageInterface::class);
        $messageMock->expects($this->once())->method('setId');

        $messageMock2 = $this->createMock(MessageInterface::class);
        $messageMock2->expects($this->once())->method('setId');

        $this->gateway->sendMessages([
            $messageMock,
            $messageMock2
        ]);
    }

    public function testReceiveMessage()
    {
        $message = $this->gateway->receiveMessage([]);

        $this->assertInstanceOf(MessageInterface::class, $message);
    }

    public function testReceiveDeliveryReport()
    {
        $deliveryReport = $this->gateway->receiveDeliveryReport([]);

        $this->assertInstanceOf(DeliveryReportInterface::class, $deliveryReport);
    }
}
