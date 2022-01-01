<?php

namespace AnSms\Tests\Gateway\Provider;

use AnSms\Exception\ReceiveException;
use AnSms\Exception\SendException;
use AnSms\Gateway\Provider\TelenorGateway;
use AnSms\Message\Message;
use AnSms\Message\MessageInterface;
use Http\Message\MessageFactory;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Psr\Http\Message\RequestInterface;
use Http\Adapter\Guzzle6\Client;
use Psr\Http\Message\ResponseInterface;

class TelenorGatewayTest extends TestCase
{
    /**
     * @var TelenorGateway
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

        $this->gateway = new TelenorGateway(
            'some-username',
            'some-password',
            'some-customer-id',
            'some-customer-password',
            null,
            $this->clientMock,
            $this->messageFactoryMock
        );
    }

    public function testCreateGatewayWithInvalidCredentials()
    {
        $this->expectException(\InvalidArgumentException::class);
        new TelenorGateway('', '', '', '');
    }

    public function testSendMessage()
    {
        $message = Message::create('46700123001', 'Hello world!', 'Testing');

        $url = 'https://sms-pro.net:44343/services/some-customer-id/sendsms';
        $headers = ['Authorization' => 'Basic c29tZS11c2VybmFtZTpzb21lLXBhc3N3b3Jk'];
        $body = '<?xml version="1.0" encoding="ISO-8859-1"?>'. "\n";
        $body .= "<mobilectrl_sms><header><customer_id>some-customer-id</customer_id>"
            . '<password>some-customer-password</password><from_alphanumeric>Testing</from_alphanumeric></header>'
            . '<payload><sms><message><![CDATA[Hello world!]]></message><to_msisdn>+46700123001</to_msisdn></sms>'
            . "</payload></mobilectrl_sms>\n";


        $requestMock = $this->createMock(RequestInterface::class);
        $this->messageFactoryMock->expects($this->once())
            ->method('createRequest')->with('POST', $url, $headers, $body)->willReturn($requestMock);

        $responseMock = $this->createMock(ResponseInterface::class);
        $this->clientMock->expects($this->once())->method('sendRequest')->willReturn($responseMock);

        $responseMock->method('getBody')->willReturn($this->getSuccesfullResponseXml());

        $this->gateway->sendMessage($message);
    }

    private function getSuccesfullResponseXml(): string
    {
        return '<mobilectrl_response>
                <mobilectrl_id>5aa434:eac0a56a0b:-7ffe</mobilectrl_id>
                <status>0</status>
            </mobilectrl_response>';
    }

    public function testSendMessageGeneratesError()
    {
        $messageMock = $this->createMock(MessageInterface::class);

        $requestMock = $this->createMock(RequestInterface::class);
        $this->messageFactoryMock->method('createRequest')->willReturn($requestMock);

        $responseMock = $this->createMock(ResponseInterface::class);
        $this->clientMock->expects($this->once())->method('sendRequest')->willReturn($responseMock);

        $responseBody = '<mobilectrl_response>
            <status>1</status>
            </mobilectrl_response>';
        $responseMock->method('getBody')->willReturn($responseBody);

        $this->expectException(SendException::class);
        $this->gateway->sendMessage($messageMock);
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

        $responseMock->method('getBody')->willReturn($this->getSuccesfullResponseXml());

        $this->gateway->sendMessages($messages);
    }

    public function testReceiveDeliveryReport()
    {
        $id = '12345';
        $status = 'SMS SENT';

        $data = "<mobilectrl_delivery_status>
             <mobilectrl_id>{$id}</mobilectrl_id>
             <status>0</status>
             <delivery_status>-2</delivery_status>
             <message>{$status}</message>
            </mobilectrl_delivery_status>";

        $deliveryReport = $this->gateway->receiveDeliveryReport($data);

        $this->assertSame($id, $deliveryReport->getId());
        $this->assertSame($status, $deliveryReport->getStatus());
    }

    public function testReceiveInvalidDeliveryReport()
    {
        $this->expectException(ReceiveException::class);

        $this->gateway->receiveDeliveryReport('');
    }
}
