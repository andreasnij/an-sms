<?php

namespace AnSms\Tests\Gateway;

use AnSms\Exception\ReceiveException;
use AnSms\Exception\SendException;
use AnSms\Gateway\TwilioGateway;
use AnSms\Message\Message;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Api\V2010\Account\MessageInstance as TwilioMessage;
use Twilio\Rest\Api\V2010\Account\MessageList as TwilioMessageList;
use Twilio\Rest\Client as TwilioClient;

class TwilioGatewayTest extends TestCase
{
    /** @var TwilioMessageList&MockObject */
    private MockObject $twilioMessageListMock;

    private TwilioGateway $gateway;

    protected function setUp(): void
    {
        $twilioClientMock = $this->createMock(TwilioClient::class);
        $this->twilioMessageListMock = $this->createMock(TwilioMessageList::class);
        $twilioClientMock->messages = $this->twilioMessageListMock;

        $this->gateway = new TwilioGateway(
            'some-account-sid',
            'some-auth-token',
            $twilioClientMock
        );
    }

    public function testCreateTwilioGatewayWithInvalidCredentials(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new TwilioGateway('', '');
    }

    public function testSendTwilioMessage(): void
    {
        $message = Message::create('46700100000', 'Hello world!', '46700123456');
        $messageId = '123';

        $twilioMessageMock = $this->createMock(TwilioMessage::class);
        $twilioMessageMock->sid = $messageId;

        $this->twilioMessageListMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($twilioMessageMock);

        $this->gateway->sendMessage($message);

        $this->assertSame($messageId, $message->getId());
    }

    public function testSendTwilioMessageGeneratesError(): void
    {
        $this->twilioMessageListMock
            ->expects($this->once())
            ->method('create')
            ->willThrowException(new TwilioException());

        $this->expectException(SendException::class);

        $messageMock = $this->createMock(Message::class);
        $this->gateway->sendMessage($messageMock);
    }

    public function testSendTwilioMessages(): void
    {
        $messages = [
            Message::create('46700100000', 'Hello world!'),
            Message::create('46700100000', 'Hello world!'),
        ];

        $twilioMessageMock = $this->createMock(TwilioMessage::class);
        $this->twilioMessageListMock
            ->expects($this->exactly(2))
            ->method('create')
            ->willReturn($twilioMessageMock);

        $this->gateway->sendMessages($messages);
    }

    public function testReceiveTwilioSmsMessage(): void
    {
        $data = [
            'To' => '+46700123001',
            'Body' => ($text = 'Hello!'),
            'From' => '+46700123456',
            'MessageSid' => ($id = '123'),
        ];
        $message = $this->gateway->receiveMessage($data);

        $this->assertSame('46700123001', (string) $message->getTo());
        $this->assertSame($text, $message->getText());
        $this->assertSame('46700123456', (string) $message->getFrom());
        $this->assertSame($id, $message->getId());
    }

    public function testReceiveTwilioInvalidSmsMessage(): void
    {
        $this->expectException(ReceiveException::class);

        $this->gateway->receiveMessage([]);
    }

    public function testReceiveTwilioDeliveryReport(): void
    {
        $data = [
            'MessageSid' => ($id = '12345'),
            'MessageStatus' => ($status = 'delivered'),
        ];
        $deliveryReport = $this->gateway->receiveDeliveryReport($data);

        $this->assertSame($id, $deliveryReport->getId());
        $this->assertSame($status, $deliveryReport->getStatus());
    }

    public function testReceiveTwilioInvalidDeliveryReport(): void
    {
        $this->expectException(ReceiveException::class);

        $this->gateway->receiveDeliveryReport([]);
    }
}
