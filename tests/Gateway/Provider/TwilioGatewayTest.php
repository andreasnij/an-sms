<?php

namespace Tests\Unit\Gateway\Provider;

use AnSms\Exception\ReceiveException;
use AnSms\Exception\SendException;
use AnSms\Gateway\Provider\TwilioGateway;
use AnSms\Message\Message;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Twilio\Rest\Client as TwilioClient;
use Twilio\Rest\Api\V2010\Account\MessageList as TwilioMessageList;
use Twilio\Rest\Api\V2010\Account\MessageInstance as TwilioMessage;
use Twilio\Exceptions\TwilioException;

class TwilioGatewayTest extends TestCase
{
    /** @var TwilioGateway */
    private $gateway;

    /** @var TwilioMessageList|MockObject */
    private $twilioMessageListMock;

    protected function setUp()
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

    public function testCreateGatewayWithInvalidCredentials()
    {
        $this->expectException(\InvalidArgumentException::class);
        new TwilioGateway('', '');
    }

    public function testSendMessage()
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

    public function testSendMessageGeneratesError()
    {
        $this->twilioMessageListMock
            ->expects($this->once())
            ->method('create')
            ->willThrowException(new TwilioException());

        $this->expectException(SendException::class);

        $messageMock = $this->createMock(Message::class);
        $this->gateway->sendMessage($messageMock);
    }

    public function testSendMessages()
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

    public function testReceiveSmsMessage()
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

    public function testReceiveInvalidSmsMessage()
    {
        $this->expectException(ReceiveException::class);

        $this->gateway->receiveMessage([]);
    }

    public function testReceiveDeliveryReport()
    {
        $data = [
            'MessageSid' => ($id = '12345'),
            'MessageStatus' => ($status = 'delivered'),
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
