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
use AnSms\Message\Address\AddressInterface;
use AnSms\Message\Address\Alphanumeric;
use AnSms\Message\Address\ShortCode;
use AnSms\Message\DeliveryReport\DeliveryReport;
use AnSms\Message\DeliveryReport\DeliveryReportInterface;
use AnSms\Message\Message;
use AnSms\Message\MessageInterface;
use AnSms\Message\PremiumMessageInterface;
use InvalidArgumentException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Cellsynt SMS and Premium SMS gateway.
 */
class CellsyntGateway extends AbstractHttpGateway implements GatewayInterface
{
    protected const SMS_API_ENDPOINT = 'https://se-1.cellsynt.net/sms.php';
    protected const PSMS_API_ENDPOINT = 'https://se-2.cellsynt.net/sendsms.php';

    public function __construct(
        protected string $username,
        protected string $password,
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
    ) {
        parent::__construct($httpClient, $requestFactory, $streamFactory);

        if (empty($username) || empty($password)) {
            throw new InvalidArgumentException('Cellsynt username and password are required');
        }
    }

    /**
     * @throws SendException
     */
    public function sendMessage(MessageInterface $message): void
    {
        $queryData = $this->buildSendQueryData($message);
        $query = http_build_query($queryData, '', '&');
        $request = $this->requestFactory->createRequest(
            'GET',
            $this->getApiEndpoint($message) . '?' . $query
        );

        try {
            $response = $this->httpClient->sendRequest($request);
            $content = (string) $response->getBody();

            $trackingId = $this->parseSendResponseContent($content);
            $message->setId($trackingId);
        } catch (ClientExceptionInterface $e) {
            throw new SendException($e->getMessage(), 0, $e);
        }
    }

    protected function buildSendQueryData(MessageInterface $message): array
    {
        if ($message instanceof PremiumMessageInterface) {
            return $this->buildPremiumSmsSendQueryData($message);
        }

        return $this->buildSmsSendQueryData($message);
    }

    protected function buildSmsSendQueryData(MessageInterface $message): array
    {
        $queryData = [
            'username'       => $this->username,
            'password'       => $this->password,
            'destination'    => '00' . $message->getTo(), // Add prefix according to API documentation format
            'text'           => $message->getText(),
            'charset'        => 'UTF-8',
        ];

        if ($message->getFrom()) {
            $queryData['originatortype'] = $this->getOriginatorType($message->getFrom());
            $queryData['originator'] = (string) $message->getFrom();
        }

        return $queryData;
    }

    protected function buildPremiumSmsSendQueryData(PremiumMessageInterface $message): array
    {
        $queryData = [
            'username'       => $this->username,
            'password'       => $this->password,
            'text'           => $message->getText(),
            'charset'        => 'UTF-8',
            'price'          => $message->getPrice(),
            'sessionid'      => $message->getIncomingMessageId()
        ];

        return $queryData;
    }

    protected function getApiEndpoint(MessageInterface $message): string
    {
        if ($message instanceof PremiumMessageInterface) {
            return static::PSMS_API_ENDPOINT;
        }

        return static::SMS_API_ENDPOINT;
    }

    /**
     * @throws SendException
     */
    protected function parseSendResponseContent(string $content): string
    {
        // Cellsynt sends data as ISO 8859-1
        $encodedContent = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1');

        // Result syntax is one of these two:
        // OK:<space><tracking number>
        // Error:<space><error message>
        if (stripos($encodedContent, 'Error') === 0) {
            $errorMessage = substr($content, 7);
            throw new SendException('Send message failed with error: ' . $errorMessage);
        } elseif (stripos($encodedContent, 'OK') !== 0) {
            throw new SendException('Send message failed with unknown error format: ' . $encodedContent);
        }

        $trackingId = substr($encodedContent, 4);

        return $trackingId;
    }

    protected function getOriginatorType(AddressInterface $address): string
    {
        if ($address instanceof Alphanumeric) {
            return 'alpha';
        } elseif ($address instanceof ShortCode) {
            return 'shortcode';
        }

        return 'numeric';
    }

    /**
     * @param Message[] $messages
     * @throws SendException
     */
    public function sendMessages(array $messages): void
    {
         // This could be optimized by using Cellsynt's multiple recipients API instead
        foreach ($messages as $message) {
            $this->sendMessage($message);
        }
    }

    /**
     * Receive message.
     *
     * Cellsynt example request for SMS: /sms/incoming?destination=46700123456originator=46700123456&text=ok
     * For premium SMS: /psms/incoming?country=se&operator=telia&shortcode=72456&
     * sender=0046700123456&text=ABC+test!&sessionid=1:1136364712521:0046700123456
     *
     * @throws ReceiveException
     */
    public function receiveMessage(array $data): MessageInterface
    {
        if (!is_array($data)) {
            throw new ReceiveException(sprintf(
                'Invalid receive message data. Data received: %s',
                var_export($data, true)
            ));
        }

        if (isset($data['sessionid'])) {
            return $this->receivePremiumSmsMessage($data);
        }

        return $this->receiveSmsMessage($data);
    }

    /**
     * Receive SMS message.
     *
     * Cellsynt example request for SMS: /sms/incoming?destination=46700123456originator=46700123456&text=ok
     * For premium SMS: /psms/incoming?country=se&operator=telia&shortcode=72456&
     * sender=0046700123456&text=ABC+test!&sessionid=1:1136364712521:0046700123456
     *
     * @throws ReceiveException
     */
    protected function receiveSmsMessage(array $data): MessageInterface
    {
        if (empty($data['text']) || empty($data['destination'])
            || empty($data['originator'])
        ) {
            throw new ReceiveException(sprintf(
                'Invalid receive message data. Data received: %s',
                var_export($data, true)
            ));
        }

        $receivedMessage = Message::create(
            $data['destination'],
            mb_convert_encoding(trim($data['text']), 'UTF-8', 'ISO-8859-1'), // Cellsynt sends data as ISO 8859-1
            $data['originator']
        );

        // Cellsynt doesn't expose any reference id for normal incoming sms, so we create our own
        $generatedId = uniqid();
        $receivedMessage->setId($generatedId);

        return $receivedMessage;
    }

    /**
     * Receive premium SMS.
     *
     * Cellsynt example request: Example request: /psms/incoming?country=se&operator=telia&shortcode=72456&
     * sender=0046700123456&text=ABC+test!&sessionid=1:1136364712521:0046700123456
     *
     * @throws ReceiveException
     */
    public function receivePremiumSmsMessage(array $data): MessageInterface
    {
        if (empty($data['country']) || empty($data['operator']) || empty($data['shortcode'])
             || empty($data['sender']) || empty($data['text']) || empty($data['sessionid'])
        ) {
            throw new ReceiveException(sprintf(
                'Invalid receive premium message data. Data received: %s',
                var_export($data, true)
            ));
        }

        $receivedMessage = Message::create(
            $data['shortcode'],
            mb_convert_encoding(trim($data['text']), 'UTF-8', 'ISO-8859-1'), // Cellsynt sends data as ISO 8859-1
            ltrim($data['sender'], '0')
        );

        $receivedMessage->setId($data['sessionid']);
        $receivedMessage->setOperator($data['operator']);
        $receivedMessage->setCountryCode(strtoupper($data['country']));

        return $receivedMessage;
    }

    /**
     * Receive message delivery report.
     *
     * Cellsynt example request: http://www.example.com/status.php?trackingid=e1066ca059
     * abb8661ffc059ed842c3cf&status=delivered
     *
     * @throws ReceiveException
     */
    public function receiveDeliveryReport(array $data): DeliveryReportInterface
    {
        if (empty($data['trackingid']) || empty($data['status'])) {
            throw new ReceiveException(sprintf(
                'Invalid message delivery report data. Data received: %s',
                var_export($data, true)
            ));
        }

        return new DeliveryReport($data['trackingid'], $data['status']);
    }
}
