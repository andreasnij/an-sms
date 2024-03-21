<?php

namespace AnSms\Tests\Gateway;

use AnSms\Exception\ReceiveException;
use AnSms\Exception\SendException;
use AnSms\Gateway\CellsyntGateway;
use AnSms\Message\Address\AddressInterface;
use AnSms\Message\Address\Alphanumeric;
use AnSms\Message\Address\PhoneNumber;
use AnSms\Message\Address\ShortCode;
use AnSms\Message\Message;
use AnSms\Message\MessageInterface;
use AnSms\Message\PremiumMessage;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use PHPUnit\Framework\Attributes\DataProvider;

class CellsyntGatewayTest extends TestCase
{
    use HttpGatewayMocksTrait;

    private CellsyntGateway $gateway;

    protected function setUp(): void
    {
        $this->createHttpGatewayMocks();

        $this->gateway = new CellsyntGateway(
            'some-username',
            'some-password',
            $this->httpClientMock,
            $this->requestFactoryMock,
            $this->streamFactoryMock,
        );
    }

    public function testCreateCellsyntGatewayWithInvalidCredentials(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CellsyntGateway(
            '',
            '',
            $this->httpClientMock,
            $this->requestFactoryMock,
            $this->streamFactoryMock,
        );
    }

    public function testSendCellsyntMessage(): void
    {
        $message = Message::create('46700123001', 'Hello world!', '46700123456');

        $url = 'https://se-1.cellsynt.net/sms.php?username=some-username&password=some-password';
        $url .= '&destination=0046700123001&text=Hello+world%21&charset=UTF-8';
        $url .= '&originatortype=numeric&originator=46700123456';

        $requestMock = $this->createMock(RequestInterface::class);
        $this->requestFactoryMock->expects($this->once())
            ->method('createRequest')->with('GET', $url)->willReturn($requestMock);

        $responseMock = $this->createMock(ResponseInterface::class);
        $this->httpClientMock->expects($this->once())->method('sendRequest')->willReturn($responseMock);

        $responseMock->method('getBody')->willReturn(Utils::streamFor('OK: 12345'));

        $this->gateway->sendMessage($message);
    }

    public function testSendCellsyntMessageGeneratesError(): void
    {
        $messageMock = $this->createMock(MessageInterface::class);

        $requestMock = $this->createMock(RequestInterface::class);
        $this->requestFactoryMock->method('createRequest')->willReturn($requestMock);

        $responseMock = $this->createMock(ResponseInterface::class);
        $this->httpClientMock->expects($this->once())->method('sendRequest')->willReturn($responseMock);

        $responseMock->method('getBody')->willReturn(Utils::streamFor('Error: Some error message'));

        $this->expectException(SendException::class);
        $this->gateway->sendMessage($messageMock);
    }

    public function testSendCellsyntPremiumSmsMessage(): void
    {
        $incomingMessage = Message::create('46700123001', 'Hello world!', '46700123456');
        $incomingMessage->setId('123');

        $premiumMessage = PremiumMessage::createFromIncomingMessage('Thank you!', 5, $incomingMessage);

        $url = 'https://se-2.cellsynt.net/sendsms.php?username=some-username&password=some-password';
        $url .= '&text=Thank+you%21&charset=UTF-8&price=5&sessionid=123';

        $requestMock = $this->createMock(RequestInterface::class);
        $this->requestFactoryMock->expects($this->once())
            ->method('createRequest')->with('GET', $url)->willReturn($requestMock);

        $responseMock = $this->createMock(ResponseInterface::class);
        $this->httpClientMock->expects($this->once())->method('sendRequest')->willReturn($responseMock);

        $responseMock->method('getBody')->willReturn(Utils::streamFor('OK: 12345'));

        $this->gateway->sendMessage($premiumMessage);
    }


    public function testSendCellsyntMessages(): void
    {
        $messages = [
            Message::create('46700123001', 'Hello world!'),
            Message::create('46700123001', 'Hello world!'),
        ];

        $requestMock = $this->createMock(RequestInterface::class);
        $this->requestFactoryMock->method('createRequest')->willReturn($requestMock);

        $responseMock = $this->createMock(ResponseInterface::class);
        $this->httpClientMock->expects($this->exactly(2))->method('sendRequest')->willReturn($responseMock);

        $responseMock->method('getBody')->willReturn(Utils::streamFor('OK: 12345'));

        $this->gateway->sendMessages($messages);
    }

    public function testReceiveCellsyntSmsMessage(): void
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

    public function testReceiveCellsyntInvalidSmsMessage(): void
    {
        $this->expectException(ReceiveException::class);

        $this->gateway->receiveMessage([]);
    }

    public function testReceiveCellsyntPremiumSmsMessage(): void
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

    public function testReceiveCellsyntInvalidPremiumSmsMessage(): void
    {
        $this->expectException(ReceiveException::class);

        $this->gateway->receiveMessage(['sessionid' => '123']);
    }

    public function testReceiveCellsyntDeliveryReport(): void
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

    public function testReceiveCellsyntInvalidDeliveryReport(): void
    {
        $this->expectException(ReceiveException::class);

        $this->gateway->receiveDeliveryReport([]);
    }

    #[DataProvider('addressTypeDataProvider')]
    public function testGetCellsyntOriginatorTypeReturnsCorrectType(
        AddressInterface $address,
        string $expectedType
    ): void {
        $reflectionClass = new \ReflectionClass(CellsyntGateway::class);
        $reflectionMethod = $reflectionClass->getMethod('getOriginatorType');
        $reflectionMethod->setAccessible(true);

        $type = $reflectionMethod->invokeArgs($this->gateway, [$address]);
        $this->assertSame($expectedType, $type);
    }

    public static function addressTypeDataProvider(): array
    {
        return [
            [new PhoneNumber('46700123456'), 'numeric'],
            [new Alphanumeric('abc'), 'alpha'],
            [new ShortCode('12345'), 'shortcode'],
        ];
    }
}
