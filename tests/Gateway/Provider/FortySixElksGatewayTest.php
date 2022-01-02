<?php

namespace AnSms\Tests\Gateway\Provider;

use AnSms\Exception\ReceiveException;
use AnSms\Exception\SendException;
use AnSms\Gateway\Provider\FortySixElksGateway;
use AnSms\Message\Message;
use AnSms\Message\MessageInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class FortySixElksGatewayTest extends TestCase
{
    use HttpGatewayMocksTrait;

    private FortySixElksGateway $gateway;

    protected function setUp(): void
    {
        $this->createHttpGatewayMocks();

        $this->gateway = new FortySixElksGateway(
            'some-username',
            'some-password',
            $this->clientMock,
            $this->requestFactoryMock,
            $this->streamFactoryMock,
        );
    }

    public function testCreateElkGatewayWithInvalidCredentials(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new FortySixElksGateway(
            '',
            '',
            $this->clientMock,
            $this->requestFactoryMock,
            $this->streamFactoryMock,
        );
    }

    public function testSendElkMessage(): void
    {
        $message = Message::create('46700123001', 'Hello world!', 'Forty6Elks');

        $url = 'https://api.46elks.com/a1/SMS';

        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->method('withHeader')->willReturnSelf();
        $requestMock->method('withBody')->willReturnSelf();

        $this->requestFactoryMock->expects($this->once())
            ->method('createRequest')->with('POST', $url)->willReturn($requestMock);

        $responseMock = $this->createMock(ResponseInterface::class);
        $this->clientMock->expects($this->once())->method('sendRequest')->willReturn($responseMock);

        $responseMock->method('getBody')->willReturn(json_encode([
            'status' => 'created',
            'direction' => 'outgoing',
            'from' => 'Forty6Elks',
            'created' => '2018-09-27T06:50:35.559577',
            'parts' => 1,
            'to' => '+46700123001',
            'cost' => 3500,
            'message' => 'Hello world!',
            'id' => 'a95b04cf23d7f94c508e675b38eb46934',
        ]));

        $this->gateway->sendMessage($message);
        $this->assertEquals('a95b04cf23d7f94c508e675b38eb46934', $message->getId());
        $this->assertEquals(1, $message->getSegmentCount());
    }

    public function testSendElkMessageWithInvalidJsonGeneratesError(): void
    {
        $messageMock = $this->createMock(MessageInterface::class);

        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->method('withHeader')->willReturnSelf();
        $requestMock->method('withBody')->willReturnSelf();
        $this->requestFactoryMock->method('createRequest')->willReturn($requestMock);

        $responseMock = $this->createMock(ResponseInterface::class);
        $this->clientMock->expects($this->once())->method('sendRequest')->willReturn($responseMock);

        $responseMock->method('getBody')->willReturn('Some error message');

        $this->expectException(SendException::class);
        $this->expectExceptionMessage('Send message failed with error: Some error message');
        $this->gateway->sendMessage($messageMock);
    }

    public function testSendElkMessageResonseWithMissingStatusKeyGeneratesError(): void
    {
        $messageMock = $this->createMock(MessageInterface::class);

        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->method('withHeader')->willReturnSelf();
        $requestMock->method('withBody')->willReturnSelf();
        $this->requestFactoryMock->method('createRequest')->willReturn($requestMock);

        $responseMock = $this->createMock(ResponseInterface::class);
        $this->clientMock->expects($this->once())->method('sendRequest')->willReturn($responseMock);

        $responseMock->method('getBody')->willReturn('{}');

        $this->expectException(SendException::class);
        $this->expectExceptionMessage('Send message failed with missing status value: {}');
        $this->gateway->sendMessage($messageMock);
    }

    public function testSendElkMessageResonseWithMissingIdKeyGeneratesError(): void
    {
        $messageMock = $this->createMock(MessageInterface::class);

        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->method('withHeader')->willReturnSelf();
        $requestMock->method('withBody')->willReturnSelf();
        $this->requestFactoryMock->method('createRequest')->willReturn($requestMock);

        $responseMock = $this->createMock(ResponseInterface::class);
        $this->clientMock->expects($this->once())->method('sendRequest')->willReturn($responseMock);

        $responseMock->method('getBody')->willReturn('{"status": "created"}');

        $this->expectException(SendException::class);
        $this->expectExceptionMessage('Message sent but missing id in response: {"status": "created"}');
        $this->gateway->sendMessage($messageMock);
    }

    public function testSendElkMessages(): void
    {
        $messages = [
            Message::create('46700123001', 'Hello world!', 'Forty6Elks'),
            Message::create('46700123001', 'Hello world!', 'Forty6Elks'),
        ];

        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->method('withHeader')->willReturnSelf();
        $requestMock->method('withBody')->willReturnSelf();
        $this->requestFactoryMock->method('createRequest')->willReturn($requestMock);

        $responseMock = $this->createMock(ResponseInterface::class);
        $this->clientMock->expects($this->exactly(2))->method('sendRequest')->willReturn($responseMock);

        $responseMock->method('getBody')->willReturn(json_encode([
            'status' => 'created',
            'direction' => 'outgoing',
            'from' => 'Forty6Elks',
            'created' => '2018-09-27T06:50:35.559577',
            'parts' => 1,
            'to' => '+46700123001',
            'cost' => 3500,
            'message' => 'Hello world!',
            'id' => 'a95b04cf23d7f94c508e675b38eb46934',
        ]));

        $this->gateway->sendMessages($messages);
    }

    public function testReceiveElkSmsMessage(): void
    {
        $id = 'sc3b36dc364f9f55ff0dcb52124aeacf7';
        $to = '46700123001';
        $text = 'Hello!';
        $from = '46700123456';

        $data = [
            'id' => $id,
            'to' => $to,
            'message' => $text,
            'from' => $from
        ];
        $message = $this->gateway->receiveMessage($data);

        $this->assertSame($to, (string) $message->getTo());
        $this->assertSame($text, $message->getText());
        $this->assertSame($from, (string) $message->getFrom());
        $this->assertSame($id, $message->getId());
    }

    public function testReceiveElkInvalidSmsMessage(): void
    {
        $this->expectException(ReceiveException::class);

        $this->gateway->receiveMessage([]);
    }

    public function testReceiveDeliveryReport(): void
    {
        $id = 'sc3b36dc364f9f55ff0dcb52124aeacf7';
        $status = 'delivered';

        $data = [
            'id' => $id,
            'status' => $status
        ];
        $deliveryReport = $this->gateway->receiveDeliveryReport($data);

        $this->assertSame($id, $deliveryReport->getId());
        $this->assertSame($status, $deliveryReport->getStatus());
    }

    public function testReceiveElkInvalidDeliveryReport(): void
    {
        $this->expectException(ReceiveException::class);

        $this->gateway->receiveDeliveryReport([]);
    }
}
