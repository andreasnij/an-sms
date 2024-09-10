<?php

namespace AnSms\Tests\Gateway;

use AnSms\Exception\ReceiveException;
use AnSms\Exception\SendException;
use AnSms\Gateway\TelenorGateway;
use AnSms\Message\Message;
use AnSms\Message\MessageInterface;
use GuzzleHttp\Psr7\Stream;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Utils;

class TelenorGatewayTest extends TestCase
{
    use HttpGatewayMocksTrait;

    private TelenorGateway$gateway;

    protected function setUp(): void
    {
        $this->createHttpGatewayMocks();

        $this->gateway = new TelenorGateway(
            'some-username',
            'some-password',
            'some-customer-id',
            'some-customer-password',
            null,
            $this->httpClientMock,
            $this->requestFactoryMock,
            $this->streamFactoryMock,
            null,
        );
    }

    public function testCreateTelenorGatewayWithInvalidCredentials(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new TelenorGateway(
            '',
            '',
            '',
            '',
            null,
            $this->httpClientMock,
            $this->requestFactoryMock,
            $this->streamFactoryMock,
        );
    }

    public function testSendTelenorMessage(): void
    {
        $message = Message::create('46700123001', 'Hello world!', 'Testing');

        $url = 'https://sms-pro.net:44343/services/some-customer-id/sendsms';

        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->method('withHeader')->willReturnSelf();
        $requestMock->method('withBody')->willReturnSelf();
        $this->requestFactoryMock->expects($this->once())
            ->method('createRequest')->with('POST', $url)->willReturn($requestMock);

        $responseMock = $this->createMock(ResponseInterface::class);
        $this->httpClientMock->expects($this->once())->method('sendRequest')->willReturn($responseMock);

        $responseMock->method('getBody')->willReturn(Utils::streamFor($this->getSuccessfulResponseXml()));

        $this->gateway->sendMessage($message);
    }

    private function getSuccessfulResponseXml(): string
    {
        return '<mobilectrl_response>
                <mobilectrl_id>5aa434:eac0a56a0b:-7ffe</mobilectrl_id>
                <status>0</status>
            </mobilectrl_response>';
    }

    public function testSendTelenorMessageGeneratesError(): void
    {
        $messageMock = $this->createMock(MessageInterface::class);

        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->method('withHeader')->willReturnSelf();
        $requestMock->method('withBody')->willReturnSelf();
        $this->requestFactoryMock->method('createRequest')->willReturn($requestMock);

        $responseMock = $this->createMock(ResponseInterface::class);
        $this->httpClientMock->expects($this->once())->method('sendRequest')->willReturn($responseMock);

        $responseBody = '<mobilectrl_response>
            <status>1</status>
            </mobilectrl_response>';
        $responseMock->method('getBody')->willReturn(Utils::streamFor($responseBody));

        $this->expectException(SendException::class);
        $this->gateway->sendMessage($messageMock);
    }

    public function testSendTelenorMessages(): void
    {
        $messages = [
            Message::create('46700123001', 'Hello world!'),
            Message::create('46700123001', 'Hello world!'),
        ];

        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->method('withHeader')->willReturnSelf();
        $requestMock->method('withBody')->willReturnSelf();
        $this->requestFactoryMock->method('createRequest')->willReturn($requestMock);

        $responseMock = $this->createMock(ResponseInterface::class);
        $this->httpClientMock->expects($this->exactly(2))->method('sendRequest')->willReturn($responseMock);

        $responseMock->method('getBody')->willReturn(Utils::streamFor($this->getSuccessfulResponseXml()));

        $this->gateway->sendMessages($messages);
    }

    public function testReceiveTelenorDeliveryReport(): void
    {
        $id = '12345';
        $status = 'SMS SENT';

        $xml = "<mobilectrl_delivery_status>
             <mobilectrl_id>{$id}</mobilectrl_id>
             <status>0</status>
             <delivery_status>-2</delivery_status>
             <message>{$status}</message>
            </mobilectrl_delivery_status>";

        $deliveryReport = $this->gateway->receiveDeliveryReport(['xml' => $xml]);

        $this->assertSame($id, $deliveryReport->getId());
        $this->assertSame($status, $deliveryReport->getStatus());
    }

    public function testSendTelenorMessageWithStatusDeliveryUrl(): void
    {
        $this->gateway = new TelenorGateway(
            'some-username',
            'some-password',
            'some-customer-id',
            'some-customer-password',
            null,
            $this->httpClientMock,
            $this->requestFactoryMock,
            $this->streamFactoryMock,
            'https://example.com/api/sms/delivery',
        );

        $message = Message::create('46700123001', 'Hello world!', 'Testing');

        $url = 'https://sms-pro.net:44343/services/some-customer-id/sendsms';

        $this->streamFactoryMock->method('createStream')
            ->with($this->callback(function ($body) {
                $this->assertStringContainsString(
                    '<status_delivery_url>https://example.com/api/sms/delivery</status_delivery_url>',
                    $body
                );

                return true;
            }))
            ->willReturn($this->createMock(Stream::class));

        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->method('withHeader')->willReturnSelf();
        $requestMock->method('withBody')->willReturnSelf();
        $this->requestFactoryMock->expects($this->once())
            ->method('createRequest')->with('POST', $url)->willReturn($requestMock);

        $responseMock = $this->createMock(ResponseInterface::class);
        $this->httpClientMock->expects($this->once())->method('sendRequest')->willReturn($responseMock);

        $responseMock->method('getBody')->willReturn(Utils::streamFor($this->getSuccessfulResponseXml()));

        $this->gateway->sendMessage($message);
    }

    public function testReceiveTelenorInvalidDeliveryReport(): void
    {
        $this->expectException(ReceiveException::class);

        $this->gateway->receiveDeliveryReport([]);
    }
}
