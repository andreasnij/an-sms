<?php

namespace Tests\Unit\Gateway\Provider;

use AnSms\Exception\SendException;
use AnSms\Gateway\Provider\FortySixElksGateway;
use AnSms\Message\Message;
use AnSms\Message\MessageInterface;
use Http\Message\MessageFactory;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Psr\Http\Message\RequestInterface;
use Http\Adapter\Guzzle6\Client;
use Psr\Http\Message\ResponseInterface;

class FortySixElksGatewayTest extends TestCase
{
    /**
     * @var FortySixElksGateway
     */
    private $gateway;

    /**
     * @var MessageFactory|MockObject
     */
    private $messageFactoryMock;

    /**
     * @var Client|MockObject
     */
    private $clientMock;

    protected function setUp()
    {
        $this->clientMock = $this->createMock(Client::class);
        $this->messageFactoryMock = $this->createMock(MessageFactory::class);

        $this->gateway = new FortySixElksGateway(
            'some-username',
            'some-password',
            $this->clientMock,
            $this->messageFactoryMock
        );
    }

    public function testCreateGatewayWithInvalidCredentials()
    {
        $this->expectException(\InvalidArgumentException::class);
        new FortySixElksGateway('', '');
    }

    public function testSendMessage()
    {
        $message = Message::create('46700123001', 'Hello world!', 'Forty6Elks');

        $url = 'https://api.46elks.com/a1/SMS';
        $headers = [
            'Authorization' => 'Basic c29tZS11c2VybmFtZTpzb21lLXBhc3N3b3Jk',
            'Content-type' => 'application/x-www-form-urlencoded',
        ];
        $query = 'from=Forty6Elks&to=%2B46700123001&message=Hello+world%21';

        $requestMock = $this->createMock(RequestInterface::class);
        $this->messageFactoryMock->expects($this->once())
            ->method('createRequest')->with('POST', $url, $headers, $query)->willReturn($requestMock);

        $responseMock = $this->createMock(ResponseInterface::class);
        $this->clientMock->expects($this->once())->method('sendRequest')->willReturn($responseMock);

        $responseMock->method('getBody')->willReturn('{"status": "created", "direction": "outgoing", "from": "Forty6Elks", "created": "2018-09-27T06:50:35.559577", "parts": 1, "to": "+46700123001", "cost": 3500, "message": "Hello world!", "id": "a95b04cf23d7f94c508e675b38eb46934"}');

        $this->gateway->sendMessage($message);
    }

    public function testSendMessageWithInvalidJsonGeneratesError()
    {
        $messageMock = $this->createMock(MessageInterface::class);

        $requestMock = $this->createMock(RequestInterface::class);
        $this->messageFactoryMock->method('createRequest')->willReturn($requestMock);

        $responseMock = $this->createMock(ResponseInterface::class);
        $this->clientMock->expects($this->once())->method('sendRequest')->willReturn($responseMock);

        $responseMock->method('getBody')->willReturn('Some error message');

        $this->expectException(SendException::class);
        $this->expectExceptionMessage('Send message failed with error: Some error message');
        $this->gateway->sendMessage($messageMock);
    }

    public function testSendMessageResonseWithMissingStatusKeyGeneratesError()
    {
        $messageMock = $this->createMock(MessageInterface::class);

        $requestMock = $this->createMock(RequestInterface::class);
        $this->messageFactoryMock->method('createRequest')->willReturn($requestMock);

        $responseMock = $this->createMock(ResponseInterface::class);
        $this->clientMock->expects($this->once())->method('sendRequest')->willReturn($responseMock);

        $responseMock->method('getBody')->willReturn('{}');

        $this->expectException(SendException::class);
        $this->expectExceptionMessage('Send message failed with missing status value: {}');
        $this->gateway->sendMessage($messageMock);
    }

    public function testSendMessageResonseWithMissingIdKeyGeneratesError()
    {
        $messageMock = $this->createMock(MessageInterface::class);

        $requestMock = $this->createMock(RequestInterface::class);
        $this->messageFactoryMock->method('createRequest')->willReturn($requestMock);

        $responseMock = $this->createMock(ResponseInterface::class);
        $this->clientMock->expects($this->once())->method('sendRequest')->willReturn($responseMock);

        $responseMock->method('getBody')->willReturn('{"status": "created"}');

        $this->expectException(SendException::class);
        $this->expectExceptionMessage('Message sent but missing id in response: {"status": "created"}');
        $this->gateway->sendMessage($messageMock);
    }

    public function testSendMessages()
    {
        $messages = [
            Message::create('46700123001', 'Hello world!', 'Forty6Elks'),
            Message::create('46700123001', 'Hello world!', 'Forty6Elks'),
        ];

        $requestMock = $this->createMock(RequestInterface::class);
        $this->messageFactoryMock->method('createRequest')->willReturn($requestMock);

        $responseMock = $this->createMock(ResponseInterface::class);
        $this->clientMock->expects($this->exactly(2))->method('sendRequest')->willReturn($responseMock);

        $responseMock->method('getBody')->willReturn('{"status": "created", "direction": "outgoing", "from": "Forty6Elks", "created": "2018-09-27T06:50:35.559577", "parts": 1, "to": "+46700123001", "cost": 3500, "message": "Hello world!", "id": "a95b04cf23d7f94c508e675b38eb46934"}');

        $this->gateway->sendMessages($messages);
    }
}
