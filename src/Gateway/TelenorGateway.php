<?php declare(strict_types=1);

/**
 * An SMS library.
 *
 * @copyright Copyright (c) 2017 Andreas Nilsson
 * @license   MIT
 */

namespace AnSms\Gateway;

use AnSms\Exception\ReceiveException;
use AnSms\Exception\SendException;
use AnSms\Message\Address\Alphanumeric;
use AnSms\Message\Address\PhoneNumber;
use AnSms\Message\DeliveryReport\DeliveryReport;
use AnSms\Message\DeliveryReport\DeliveryReportInterface;
use AnSms\Message\Message;
use AnSms\Message\MessageInterface;
use DOMDocument;
use DOMElement;
use InvalidArgumentException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Telenor SMS Pro SMS gateway.
 */
class TelenorGateway extends AbstractHttpGateway implements GatewayInterface
{
    protected const API_ENDPOINT_TEMPLATE = 'https://sms-pro.net:44343/services/%s/sendsms';

    public function __construct(
        protected string $username,
        protected string $password,
        protected string $customerId,
        protected string $customerPassword,
        protected ?string $supplementaryInformation = null,
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
    ) {
        parent::__construct($httpClient, $requestFactory, $streamFactory);

        if (empty($username) || empty($password) || empty($customerId) || empty($customerPassword)) {
            throw new InvalidArgumentException('Sms Pro username and password, customer id and password are required');
        }
    }

    /**
     * @throws SendException
     */
    public function sendMessage(MessageInterface $message): void
    {
        $request = $this->requestFactory->createRequest('POST', $this->getApiEndpoint())
            ->withHeader('Authorization', $this->getAuthorizationHeaderValue())
            ->withBody($this->streamFactory->createStream($this->buildSendBody($message)));

        try {
            $response = $this->httpClient->sendRequest($request);

            $content = (string) $response->getBody();
            $trackingId = $this->parseSendResponseContent($content);
            $message->setId($trackingId);
        } catch (ClientExceptionInterface $e) {
            throw new SendException($e->getMessage(), 0, $e);
        }
    }

    protected function getApiEndpoint(): string
    {
        return sprintf(self::API_ENDPOINT_TEMPLATE, $this->customerId);
    }

    protected function getAuthorizationHeaderValue(): string
    {
        return 'Basic ' . base64_encode(sprintf('%s:%s', $this->username, $this->password));
    }

    protected function buildSendBody(MessageInterface $message): string
    {
        $xml = new DOMDocument('1.0', 'ISO-8859-1');

        $mobileCtrlSms = $xml->createElement('mobilectrl_sms');

        $header = $xml->createElement('header');
        $header->appendChild($xml->createElement('customer_id', $this->customerId));
        $header->appendChild($xml->createElement('password', $this->customerPassword));
        if (($messageId = $message->getId()) !== null) {
            $header->appendChild($xml->createElement('request_id', $messageId));
        }
        if (($xmlChild = $this->getMessageFromXmlChild($message, $xml))) {
            $header->appendChild($xmlChild);
        }
        if ($this->supplementaryInformation !== null) {
            $header->appendChild($xml->createElement('sub_id_1', $this->supplementaryInformation));
        }
        $mobileCtrlSms->appendChild($header);

        $payload = $xml->createElement('payload');
        $sms = $xml->createElement('sms');
        $sms->appendChild(($messageElement = $xml->createElement('message')));
        $messageElement->appendChild($xml->createCDATASection($message->getText()));
        $sms->appendChild($xml->createElement('to_msisdn', '+' . $message->getTo()));
        $payload->appendChild($sms);
        $mobileCtrlSms->appendChild($payload);

        $xml->appendChild($mobileCtrlSms);

        return (string) $xml->saveXML();
    }

    protected function getMessageFromXmlChild(MessageInterface $message, DOMDocument $xmlDocument): ?DOMElement
    {
        if ($message->getFrom() === null) {
            return null;
        }

        if ($message->getFrom() instanceof PhoneNumber) {
            return $xmlDocument->createElement('from_msisdn', '+' . $message->getFrom());
        } elseif ($message->getFrom() instanceof Alphanumeric) {
            return $xmlDocument->createElement('from_alphanumeric', (string) $message->getFrom());
        }

        throw new SendException("Unsupported message from adress type");
    }

    /**
     * @throws SendException
     */
    protected function parseSendResponseContent(string $content): string
    {
        $xml = @simplexml_load_string($content);
        if ($xml === false) {
            throw new SendException('Could not parse send XML response: ' . $content);
        }

        if (!isset($xml->status) || (int) $xml->status !== 0) {
            throw new SendException('Send message failed with error: ' . ($xml->message ?? ''));
        }

        $trackingId = (string) $xml->mobilectrl_id;

        return $trackingId;
    }

    /**
     * @param Message[] $messages
     * @throws SendException
     */
    public function sendMessages(array $messages): void
    {
        foreach ($messages as $message) {
            $this->sendMessage($message);
        }
    }

    /**
     * Not implemented/available.
     */
    public function receiveMessage(array $data): MessageInterface
    {
        throw new \LogicException('Not implemented');
    }

    /**
     * @throws ReceiveException
     */
    public function receiveDeliveryReport(array $data): DeliveryReportInterface
    {
        if (empty($data['xml']) || !is_string($data['xml'])) {
            throw new ReceiveException(sprintf(
                'Invalid message delivery report data. Data received: %s',
                var_export($data, true)
            ));
        }

        $xml = @simplexml_load_string($data['xml']);
        if ($xml === false) {
            throw new ReceiveException('Could not parse delivery report XML: ' . var_export($data, true));
        }

        if (empty($xml->mobilectrl_id) || empty($xml->message)) {
            throw new ReceiveException(sprintf(
                'Invalid delivery report data. Data received: %s',
                var_export($data, true)
            ));
        }

        return new DeliveryReport(
            (string) $xml->mobilectrl_id,
            (string) $xml->message
        );
    }
}
