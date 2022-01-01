<?php

namespace AnSms\Tests\Gateway\Provider;

use AnSms\Exception\ReceiveException;
use AnSms\Exception\SendException;
use AnSms\Gateway\Provider\CellsyntGateway;
use AnSms\Message\Address\AddressInterface;
use AnSms\Message\Address\Alphanumeric;
use AnSms\Message\Address\PhoneNumber;
use AnSms\Message\Address\ShortCode;
use AnSms\Message\Message;
use AnSms\Message\MessageInterface;
use AnSms\Message\PremiumMessage;
use Http\Message\MessageFactory;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Psr\Http\Message\RequestInterface;
use Http\Adapter\Guzzle6\Client;
use Psr\Http\Message\ResponseInterface;

class CellsyntGatewayTest extends TestCase
{
    /**
     * @var CellsyntGateway
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

        $this->gateway = new CellsyntGateway(
            'some-username',
            'some-password',
            $this->clientMock,
            $this->messageFactoryMock
        );
    }

    public function testCreateGatewayWithInvalidCredentials()
    {
        $this->expectException(\InvalidArgumentException::class);
        new CellsyntGateway('', '');
    }

    public function testSendMessage()
    {
        $message = Message::create('46700123001', 'Hello world!', '46700123456');

        $url = 'https://se-1.cellsynt.net/sms.php?username=some-username&password=some-password';
        $url .= '&destination=0046700123001&text=Hello+world%21&charset=UTF-8';
        $url .= '&originatortype=numeric&originator=46700123456';

        $requestMock = $this->createMock(RequestInterface::class);
        $this->messageFactoryMock->expects($this->once())
            ->method('createRequest')->with('GET', $url)->willReturn($requestMock);

        $responseMock = $this->createMock(ResponseInterface::class);
        $this->clientMock->expects($this->once())->method('sendRequest')->willReturn($responseMock);

        $responseMock->method('getBody')->willReturn('OK: 12345');

        $this->gateway->sendMessage($message);
    }

    public function testSendMessageGeneratesError()
    {
        $messageMock = $this->createMock(MessageInterface::class);

        $requestMock = $this->createMock(RequestInterface::class);
        $this->messageFactoryMock->method('createRequest')->willReturn($requestMock);

        $responseMock = $this->createMock(ResponseInterface::class);
        $this->clientMock->expects($this->once())->method('sendRequest')->willReturn($responseMock);

        $responseMock->method('getBody')->willReturn('Error: Some error message');

        $this->expectException(SendException::class);
        $this->gateway->sendMessage($messageMock);
    }

    public function testSendPremiumSmsMessage()
    {
        $incomingMessage = Message::create('46700123001', 'Hello world!', '46700123456');
        $incomingMessage->setId('123');

        $premiumMessage = PremiumMessage::createFromIncomingMessage('Thank you!', 5, $incomingMessage);

        $url = 'https://se-2.cellsynt.net/sendsms.php?username=some-username&password=some-password';
        $url .= '&text=Thank+you%21&charset=UTF-8&price=5&sessionid=123';

        $requestMock = $this->createMock(RequestInterface::class);
        $this->messageFactoryMock->expects($this->once())
            ->method('createRequest')->with('GET', $url)->willReturn($requestMock);

        $responseMock = $this->createMock(ResponseInterface::class);
        $this->clientMock->expects($this->once())->method('sendRequest')->willReturn($responseMock);

        $responseMock->method('getBody')->willReturn('OK: 12345');

        $this->gateway->sendMessage($premiumMessage);
    }


    public function testSendMessages()
    {
        $messages = [
            Message::create('46700123001', 'Hello world!'),
            Message::create('46700123001', 'Hello world!'),
        ];

        $requestMock = $this->createMock(RequestInterface::class);
        $this->messageFactoryMock->method('createRequest')->willReturn($requestMock);

        $responseMock = $this->createMock(ResponseInterface::class);
        $this->clientMock->expects($this->exactly(2))->method('sendRequest')->willReturn($responseMock);

        $responseMock->method('getBody')->willReturn('OK: 12345');

        $this->gateway->sendMessages($messages);
    }

    public function testReceiveSmsMessage()
    {
        $to = '46700123001';
        $text = 'Hello!';
        $from = '46700123456';

        $data = [
            'destination' => $to,
            'text' => $text,
            'originator' => $from
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

    public function testReceivePremiumSmsMessage()
    {
        $to = '12345';
        $text = 'Hello!';
        $from = '46700123456';
        $operator = 'Company';
        $countryCode = 'SE';
        $id = '123';

        $data = [
            'shortcode' => $to,
            'text' => $text,
            'sender' => $from,
            'operator' => $operator,
            'country' => $countryCode,
            'sessionid' => $id,
        ];
        $message = $this->gateway->receiveMessage($data);

        $this->assertSame($to, (string) $message->getTo());
        $this->assertSame($text, $message->getText());
        $this->assertSame($from, (string) $message->getFrom());
        $this->assertSame($operator, $message->getOperator());
        $this->assertSame($countryCode, $message->getCountryCode());
        $this->assertSame($id, $message->getId());
    }

    public function testReceiveInvalidPremiumSmsMessage()
    {
        $this->expectException(ReceiveException::class);

        $this->gateway->receiveMessage(['sessionid' => '123']);
    }

    public function testReceiveDeliveryReport()
    {
        $id = '12345';
        $status = 'delivered';

        $data = [
            'trackingid' => $id,
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

    /**
     * @dataProvider addressTypeProvider
     */
    public function testGetOriginatorTypeReturnsCorrectType(AddressInterface $address, string $expectedType)
    {
        $reflectionClass = new \ReflectionClass(CellsyntGateway::class);
        $reflectionMethod = $reflectionClass->getMethod('getOriginatorType');
        $reflectionMethod->setAccessible(true);

        $type = $reflectionMethod->invokeArgs($this->gateway, [$address]);
        $this->assertSame($expectedType, $type);
    }

    public function addressTypeProvider(): array
    {
        return [
            [new PhoneNumber('46700123456'), 'numeric'],
            [new Alphanumeric('abc'), 'alpha'],
            [new ShortCode('12345'), 'shortcode'],
        ];
    }
}
