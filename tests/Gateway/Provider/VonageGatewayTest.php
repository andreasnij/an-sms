<?php

namespace AnSms\Tests\Gateway\Provider;

use AnSms\Exception\ReceiveException;
use AnSms\Exception\SendException;
use AnSms\Gateway\Provider\VonageGateway;
use AnSms\Message\Message;
use AnSms\Message\MessageInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Vonage\Client as VonageClient;
use Vonage\Message\Client as VonageMessageClient;
use Vonage\Message\Message as VonageMessage;
use Vonage\Client\Exception\Exception as VonageClientException;

class VonageGatewayTest extends TestCase
{
    /** @var VonageMessageClient&MockObject  */
    private MockObject $vonageMessageClientMock;

    private VonageGateway $gateway;

    protected function setUp(): void
    {
        $this->vonageMessageClientMock = $this->createMock(VonageMessageClient::class);
        $vonageClientMock = $this->createMock(VonageClient::class);
        $vonageClientMock->method('__call')->with('message')->willReturn($this->vonageMessageClientMock);

        $this->gateway = new VonageGateway(
            'some-key',
            'some-secret',
            $vonageClientMock,
        );
    }

    public function testCreateVonageGatewayWithInvalidCredentials(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new VonageGateway('', '');
    }

    public function testSendVonageMessage(): void
    {
        $message = Message::create('46700100000', 'Hello world!', '46700123456');
        $messageId = '123';

        $vonageMessageMock = $this->createMock(VonageMessage::class);
        $vonageMessageMock->method('getMessageId')->willReturn($messageId);

        $this->vonageMessageClientMock
            ->expects($this->once())
            ->method('send')
            ->willReturn($vonageMessageMock);

        $this->gateway->sendMessage($message);

        $this->assertSame($messageId, $message->getId());
    }

    public function testSendVonageMessageGeneratesError(): void
    {
        $this->vonageMessageClientMock
            ->expects($this->once())
            ->method('send')
            ->willThrowException(new VonageClientException());

        $this->expectException(SendException::class);

        $messageMock = $this->createMock(MessageInterface::class);
        $this->gateway->sendMessage($messageMock);
    }

    public function testSendVonageMessages(): void
    {
        $messages = [
            Message::create('46700100000', 'Hello world!'),
            Message::create('46700100000', 'Hello world!'),
        ];

        $vonageMessageMock = $this->createMock(VonageMessage::class);
        $this->vonageMessageClientMock
            ->expects($this->exactly(2))
            ->method('send')
            ->willReturn($vonageMessageMock);

        $this->gateway->sendMessages($messages);
    }

    public function testReceiveVonageSmsMessage(): void
    {
        $data = [
            'to' => ($to = '46700123001'),
            'text' => ($text = 'Hello!'),
            'msisdn' => ($from = '46700123456'),
            'messageId' => ($id = '123'),
        ];
        $message = $this->gateway->receiveMessage($data);

        $this->assertSame($to, (string) $message->getTo());
        $this->assertSame($text, $message->getText());
        $this->assertSame($from, (string) $message->getFrom());
        $this->assertSame($id, $message->getId());
    }

    public function testReceiveVonageInvalidSmsMessage(): void
    {
        $this->expectException(ReceiveException::class);

        $this->gateway->receiveMessage([]);
    }

    public function testReceiveVonageDeliveryReport(): void
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

    public function testReceiveVonageInvalidDeliveryReport(): void
    {
        $this->expectException(ReceiveException::class);

        $this->gateway->receiveDeliveryReport([]);
    }
}
