<?php

namespace Tests\Unit\Gateway\Provider;

use AnSms\Exception\ReceiveException;
use AnSms\Exception\SendException;
use AnSms\Gateway\Provider\NexmoGateway;
use AnSms\Message\Message;
use AnSms\Message\MessageInterface;
use Http\Message\MessageFactory;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Http\Adapter\Guzzle6\Client;
use Nexmo\Client as NexmoClient;
use Nexmo\Message\Client as NexmoMessageClient;
use Nexmo\Message\Message as NexmoMessage;
use Nexmo\Client\Exception\Exception as NexmoClientException;

class NexmoGatewayTest extends TestCase
{
    /** @var NexmoGateway */
    private $gateway;

    /** @var Client|MockObject */
    private $clientMock;

    /** @var MessageFactory|MockObject */
    private $messageFactoryMock;

    /** @var NexmoClient|MockObject */
    private $nexmoMessageClientMock;

    protected function setUp()
    {
        $this->clientMock = $this->createMock(Client::class);
        $this->messageFactoryMock = $this->createMock(MessageFactory::class);

        $this->nexmoMessageClientMock = $this->createMock(NexmoMessageClient::class);
        $nexmoClientMock = $this->getMockBuilder(NexmoClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['message'])
            ->getMock();
        $nexmoClientMock->method('message')->willReturn($this->nexmoMessageClientMock);

        $this->gateway = new NexmoGateway(
            'some-key',
            'some-secret',
            $this->clientMock,
            $this->messageFactoryMock,
            $nexmoClientMock
        );
    }

    public function testCreateGatewayWithInvalidCredentials()
    {
        $this->expectException(\InvalidArgumentException::class);
        new NexmoGateway('', '');
    }

    public function testSendMessage()
    {
        $message = Message::create('46700100000', 'Hello world!', '46700123456');
        $messageId = '123';

        $nexmoMessageMock = $this->createMock(NexmoMessage::class);
        $nexmoMessageMock->method('getMessageId')->willReturn($messageId);

        $this->nexmoMessageClientMock
            ->expects($this->once())
            ->method('send')
            ->willReturn($nexmoMessageMock);

        $this->gateway->sendMessage($message);

        $this->assertSame($messageId, $message->getId());
    }

    public function testSendMessageGeneratesError()
    {
        $this->nexmoMessageClientMock
            ->expects($this->once())
            ->method('send')
            ->willThrowException(new NexmoClientException());

        $this->expectException(SendException::class);

        $messageMock = $this->createMock(MessageInterface::class);
        $this->gateway->sendMessage($messageMock);
    }



    public function testSendMessages()
    {
        $messages = [
            Message::create('46700100000', 'Hello world!'),
            Message::create('46700100000', 'Hello world!'),
        ];

        $nexmoMessageMock = $this->createMock(NexmoMessage::class);
        $this->nexmoMessageClientMock
            ->expects($this->exactly(2))
            ->method('send')
            ->willReturn($nexmoMessageMock);

        $this->gateway->sendMessages($messages);
    }

    public function testReceiveSmsMessage()
    {
        $to = '46700123001';
        $text = 'Hello!';
        $from = '46700123456';

        $data = [
            'to' => $to,
            'text' => $text,
            'msisdn' => $from,
            'messageId' => '123',
        ];
        $message = $this->gateway->receiveMessage($data);

        $this->assertSame($to, (string) $message->getTo());
        $this->assertSame($text, $message->getText());
        $this->assertSame($from, (string) $message->getFrom());
    }

    public function testReceiveInvalidSmsMessage()
    {
        $this->expectException(ReceiveException::class);

        $this->gateway->receiveMessage([]);
    }

    public function testReceiveDeliveryReport()
    {
        $id = '12345';
        $status = 'delivered';

        $data = [
            'messageId' => $id,
            'status' => $status
        ];
        $deliveryReport = $this->gateway->receiveDeliveryReport($data);

        $this->assertSame($id, $deliveryReport->getId());
        $this->assertSame($status, $deliveryReport->getStatus());
    }

    public function testReceiveInvalidDeliveryReport()
    {
        $this->expectException(ReceiveException::class);

        $this->gateway->receiveDeliveryReport([]);
    }
}
