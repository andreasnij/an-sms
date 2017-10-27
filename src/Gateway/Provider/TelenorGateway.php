<?php declare(strict_types=1);

/**
 * An SMS library.
 *
 * @copyright Copyright (c) 2017 Andreas Nilsson
 * @license   MIT
 */

namespace AnSms\Gateway\Provider;

use AnSms\Gateway\AbstractHttpGateway;
use AnSms\Gateway\GatewayInterface;
use AnSms\Exception\ReceiveException;
use AnSms\Exception\SendException;
use AnSms\Message\Address\Alphanumeric;
use AnSms\Message\Address\PhoneNumber;
use AnSms\Message\Message;
use AnSms\Message\MessageInterface;
use AnSms\Message\DeliveryReport\DeliveryReportInterface;
use AnSms\Message\DeliveryReport\DeliveryReport;
use DOMElement;
use Http\Client\Exception\TransferException;
use Http\Client\HttpClient;
use Http\Message\MessageFactory;
use DOMDocument;

/**
 * @author Andreas Nilsson <http://github.com/jandreasn>
 */
class TelenorGateway extends AbstractHttpGateway implements GatewayInterface
{
    protected const API_ENDPOINT_TEMPLATE = 'https://sms-pro.net:44343/services/%s/sendsms';

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var string
     */
    protected $customerId;

    /**
     * @var string
     */
    protected $customerPassword;

    /**
     * @var string|null
     */
    private $supplementaryInformation;

    public function __construct(
        string $username,
        string $password,
        string $customerId,
        string $customerPassword,
        string $supplementaryInformation = null,
        HttpClient $httpClient = null,
        MessageFactory $messageFactory = null
    ) {
        parent::__construct($httpClient, $messageFactory);

        if (empty($username) || empty($password) || empty($customerId) || empty($customerPassword)) {
            throw new \InvalidArgumentException('Sms Pro username and password, customer id and password are required');
        }

        $this->username = $username;
        $this->password = $password;
        $this->customerId = $customerId;
        $this->customerPassword = $customerPassword;
        $this->supplementaryInformation = $supplementaryInformation;
    }
    /**
     * @param MessageInterface $message
     * @throws SendException
     */
    public function sendMessage(MessageInterface $message): void
    {
        $request = $this->messageFactory->createRequest(
            'POST',
            $this->getApiEndpoint(),
            $this->getHeaders(),
            $this->buildSendBody($message)
        );

        try {
            $response = $this->httpClient->sendRequest($request);

            $content = (string) $response->getBody();
            $trackingId = $this->parseSendResponseContent($content);
            $message->setId($trackingId);
        } catch (TransferException $e) {
            throw new SendException($e->getMessage(), 0, $e);
        }
    }

    protected function getApiEndpoint(): string
    {
        return sprintf(self::API_ENDPOINT_TEMPLATE, $this->customerId);
    }

    protected function getHeaders(): array
    {
        $headers = [
            'Authorization' => 'Basic ' . base64_encode(sprintf('%s:%s', $this->username, $this->password))
        ];

        return $headers;
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
            $header->appendChild($xml->createElement('from_alphanumeric', utf8_decode((string) $message->getFrom())));
        }
        if ($this->supplementaryInformation !== null) {
            $header->appendChild($xml->createElement('sub_id_1', $this->supplementaryInformation));
        }
        $mobileCtrlSms->appendChild($header);

        $payload = $xml->createElement('payload');
        $sms = $xml->createElement('sms');
        $sms->appendChild(($messageElement = $xml->createElement('message')));
        $messageElement->appendChild($xml->createCDATASection(utf8_decode($message->getText())));
        $sms->appendChild($xml->createElement('to_msisdn', (string) $message->getTo()));
        $payload->appendChild($sms);
        $mobileCtrlSms->appendChild($payload);

        $xml->appendChild($mobileCtrlSms);

        return $xml->saveXML();
    }

    protected function getMessageFromXmlChild(MessageInterface $message, DOMDocument $xmlDocument): ?DOMElement
    {
        if ($message->getFrom() === null) {
            return null;
        }

        if ($message->getFrom() instanceof PhoneNumber) {
            return $xmlDocument->createElement('from_msisdn', (string) $message->getFrom());
        } elseif ($message->getFrom() instanceof Alphanumeric) {
            return $xmlDocument->createElement('from_alphanumeric', utf8_decode((string) $message->getFrom()));
        }

        throw new SendException("Unsupported message from adress type");
    }

    /**
     * @param string $content
     * @throws SendException
     * @return string
     */
    protected function parseSendResponseContent(string $content): string
    {
        $xml = @simplexml_load_string($content);
        if ($xml === false) {
            throw new SendException('Could not parse send XML response: ' . $content);
        }

        if (!isset($xml->status) || (int) $xml->status !== 0) {
            throw new SendException('Send message failed with error: ' . $xml->message ?? '');
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
     * {@inheritdoc}
     */
    public function receiveMessage($data): MessageInterface
    {
        throw new \LogicException('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function receiveDeliveryReport($data): DeliveryReportInterface
    {
        $xml = @simplexml_load_string($data);
        if ($xml === false) {
            throw new ReceiveException('Could not parse delivery report XML: ' . $data);
        }

        if (empty($xml->mobilectrl_id) || empty($xml->message)) {
            throw new ReceiveException(sprintf(
                'Invalid delivery report data. Data received: %s',
                $data
            ));
        }

        return new DeliveryReport(
            (string) $xml->mobilectrl_id,
            (string) $xml->message
        );
    }
}
