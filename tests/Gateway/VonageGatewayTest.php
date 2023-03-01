<?php

namespace AnSms\Tests\Gateway;

use AnSms\Exception\ReceiveException;
use AnSms\Exception\SendException;
use AnSms\Gateway\VonageGateway;
use AnSms\Message\Message;
use AnSms\Message\MessageInterface;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;

class VonageGatewayTest extends TestCase
{
    private const MOCK_RESPONSE_DATA = [
        'message-count' => '1',
        'messages' => [
            [
                'to' => '46700100000',
                'message-id' => '123',
                'status' => '0',
                'remaining-balance' => '3.14159265',
                'message-price' => '0.03330000',
                'network' => '12345',
                'client-ref' => 'my-personal-reference',
                'account-ref' => 'customer1234',
            ],
        ]
    ];

    /** @var ClientInterface&MockObject  */
    private MockObject $httpClientMock;

    private VonageGateway $gateway;

    protected function setUp(): void
    {
        $this->httpClientMock = $this->createMock(ClientInterface::class);

        $this->gateway = new VonageGateway(
            'some-key',
            'some-secret',
            null,
            $this->httpClientMock,
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

        $mockResponse = new Response(200, [], (string) json_encode(self::MOCK_RESPONSE_DATA));

        $this->httpClientMock->expects($this->once())
            ->method('sendRequest')
            ->willReturn($mockResponse);

        $this->gateway->sendMessage($message);

        $this->assertSame(self::MOCK_RESPONSE_DATA['messages'][0]['message-id'], $message->getId());
    }

    public function testSendVonageMessageGeneratesError(): void
    {
        $mockResponse = new Response(500);

        $this->httpClientMock->expects($this->once())
            ->method('sendRequest')
            ->willReturn($mockResponse);

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

        $mockResponse = new Response(200, [], (string) json_encode(self::MOCK_RESPONSE_DATA));
        $mockResponse2 = new Response(200, [], (string) json_encode(self::MOCK_RESPONSE_DATA));

        $this->httpClientMock
            ->expects($this->exactly(2))
            ->method('sendRequest')
            ->willReturnOnConsecutiveCalls($mockResponse, $mockResponse2);

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
