<?php

namespace AnSms\Tests;

use AnSms\Message\Address\PhoneNumber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use AnSms\Message\DeliveryReport\DeliveryReportInterface;
use AnSms\Message\MessageInterface;
use AnSms\SmsTransceiver;
use AnSms\Message\Message;
use AnSms\Gateway\GatewayInterface;
use Psr\Log\LoggerInterface;

class SmsTransceiverTest extends TestCase
{
    private SmsTransceiver $transceiver;

    /** @var GatewayInterface&MockObject  */
    private MockObject $gatewayMock;

    /** @var LoggerInterface&MockObject  */
    private MockObject $loggerMock;

    protected function setUp(): void
    {
        $this->gatewayMock = $this->createMock(GatewayInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->transceiver = new SmsTransceiver($this->gatewayMock, $this->loggerMock);
    }

    public function testSendMessage(): void
    {
        $this->gatewayMock->expects($this->once())->method('sendMessage');
        $this->loggerMock->expects($this->once())->method('info');

        $message = Message::create('46700123456', 'Hello world!');
        $this->transceiver->sendMessage($message);
    }

    public function testSendMessages(): void
    {
        $this->gatewayMock->expects($this->once())->method('sendMessages');
        $this->loggerMock->expects($this->exactly(2))->method('info');

        $messages = [
            Message::create('46700123001', 'Hello world!'),
            Message::create('46700123002', 'Hello world!'),
        ];
        $this->transceiver->sendMessages($messages);
    }

    public function testReceiveMessage(): void
    {
        $this->gatewayMock->expects($this->once())->method('receiveMessage');
        $this->loggerMock->expects($this->once())->method('info');

        $message = $this->transceiver->receiveMessage([]);

        $this->assertInstanceOf(MessageInterface::class, $message);
    }

    public function testReceiveDeliveryReport(): void
    {
        $this->gatewayMock->expects($this->once())->method('receiveDeliveryReport');
        $this->loggerMock->expects($this->once())->method('info');

        $deliveryReport = $this->transceiver->receiveDeliveryReport([]);

        $this->assertInstanceOf(DeliveryReportInterface::class, $deliveryReport);
    }

    public function testSetDefaultFrom(): void
    {
        $defaultFrom = 'abcd';
        $this->transceiver->setDefaultFrom($defaultFrom);
        $message = Message::create('46700123456', 'Hello world!');
        $this->transceiver->sendMessage($message);

        $this->assertSame($defaultFrom, (string) $message->getFrom());
    }

    public function testSetDefaultFromWithAddress(): void
    {
        $defaultFrom = new PhoneNumber('46700123456');
        $this->transceiver->setDefaultFrom($defaultFrom);
        $message = Message::create('46700123456', 'Hello world!');
        $this->transceiver->sendMessage($message);

        $this->assertSame((string) $defaultFrom, (string) $message->getFrom());
    }
}
