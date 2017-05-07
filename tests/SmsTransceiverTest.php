<?php

namespace Tests;

use AnSms\Message\Address\PhoneNumber;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use AnSms\Message\DeliveryReport\DeliveryReportInterface;
use AnSms\Message\MessageInterface;
use AnSms\SmsTransceiver;
use AnSms\Message\Message;
use AnSms\Gateway\GatewayInterface;
use Psr\Log\LoggerInterface;

class SmsTransceiverTest extends TestCase
{
    /**
     * @var SmsTransceiver
     */
    private $transceiver;

    /**
     * @var GatewayInterface|MockObject
     */
    private $gatewayMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    protected function setUp()
    {
        $this->gatewayMock = $this->createMock(GatewayInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->transceiver = new SmsTransceiver($this->gatewayMock, $this->loggerMock);
    }

    public function testSendMessage()
    {
        $this->gatewayMock->expects($this->once())->method('sendMessage');
        $this->loggerMock->expects($this->once())->method('info');

        $message = Message::create('46700123456', 'Hello world!');
        $this->transceiver->sendMessage($message);
    }

    public function testSendMessages()
    {
        $this->gatewayMock->expects($this->once())->method('sendMessages');
        $this->loggerMock->expects($this->exactly(2))->method('info');

        $messages = [
            Message::create('46700123001', 'Hello world!'),
            Message::create('46700123002', 'Hello world!'),
        ];
        $this->transceiver->sendMessages($messages);
    }

    public function testReceiveMessage()
    {
        $this->gatewayMock->expects($this->once())->method('receiveMessage');
        $this->loggerMock->expects($this->once())->method('info');

        $message = $this->transceiver->receiveMessage([]);

        $this->assertInstanceOf(MessageInterface::class, $message);
    }

    public function testReceiveDeliveryReport()
    {
        $this->gatewayMock->expects($this->once())->method('receiveDeliveryReport');
        $this->loggerMock->expects($this->once())->method('info');

        $deliveryReport = $this->transceiver->receiveDeliveryReport([]);

        $this->assertInstanceOf(DeliveryReportInterface::class, $deliveryReport);
    }

    public function testSetDefaultFrom()
    {
        $defaultFrom = 'abcd';
        $this->transceiver->setDefaultFrom($defaultFrom);
        $message = Message::create('46700123456', 'Hello world!');
        $this->transceiver->sendMessage($message);

        $this->assertSame($defaultFrom, (string) $message->getFrom());
    }

    public function testSetDefaultFromWithAddress()
    {
        $defaultFrom = new PhoneNumber('46700123456');
        $this->transceiver->setDefaultFrom($defaultFrom);
        $message = Message::create('46700123456', 'Hello world!');
        $this->transceiver->sendMessage($message);

        $this->assertSame((string) $defaultFrom, (string) $message->getFrom());
    }
}
