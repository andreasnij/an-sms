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
use AnSms\Message\DeliveryReport\DeliveryReport;
use AnSms\Message\DeliveryReport\DeliveryReportInterface;
use AnSms\Message\Message;
use AnSms\Message\MessageInterface;
use InvalidArgumentException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * 46Elks SMS gateway.
 */
class FortySixElksGateway extends AbstractHttpGateway implements GatewayInterface
{
    protected const SMS_API_ENDPOINT = 'https://api.46elks.com/a1/SMS';

    protected string $apiUsername;
    protected string $apiPassword;

    public function __construct(
        string $apiUsername,
        string $apiPassword,
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
    ) {
        parent::__construct($httpClient, $requestFactory, $streamFactory);

        if (empty($apiUsername) || empty($apiPassword)) {
            throw new InvalidArgumentException('46Elks api username and api password are required');
        }

        $this->apiUsername = $apiUsername;
        $this->apiPassword = $apiPassword;
    }

    /**
     * @throws SendException
     */
    public function sendMessage(MessageInterface $message): void
    {
        $data = http_build_query($this->buildSendData($message));
        $request = $this->requestFactory->createRequest('POST', self::SMS_API_ENDPOINT)
            ->withHeader('Content-type', 'application/x-www-form-urlencoded')
            ->withHeader('Authorization', $this->getAuthorizationHeaderValue())
            ->withBody($this->streamFactory->createStream($data));

        try {
            $response = $this->httpClient->sendRequest($request);
            $content = (string) $response->getBody();

            $this->parseSendResponseContent($content, $message);
        } catch (ClientExceptionInterface $e) {
            throw new SendException($e->getMessage(), 0, $e);
        }
    }

    protected function getAuthorizationHeaderValue(): string
    {
        return 'Basic ' . base64_encode(sprintf('%s:%s', $this->apiUsername, $this->apiPassword));
    }

    protected function buildSendData(MessageInterface $message): array
    {
        return [
            'from' => (string) $message->getFrom(),
            'to' => (string) $message->getTo(),
            'message' => $message->getText(),
        ];
    }

    /**
     * @throws SendException
     */
    protected function parseSendResponseContent(string $content, MessageInterface $message): void
    {
        $result = json_decode($content, true);
        if (!is_array($result)) {
            throw new SendException('Send message failed with error: ' . $content);
        } elseif (!isset($result['status']) || !in_array($result['status'], ['created', 'sent', 'delivered'])) {
            throw new SendException('Send message failed with missing status value: ' . $content);
        } elseif (!isset($result['id'])) {
            throw new SendException('Message sent but missing id in response: ' . $content);
        }

        $message->setId($result['id']);

        if (isset($result['parts'])) {
            $message->setSegmentCount((int) $result['parts']);
        }
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
     * @throws ReceiveException
     */
    public function receiveMessage(array $data): MessageInterface
    {
        if (empty($data['id']) || empty($data['from'])
            || empty($data['to']) || empty($data['message'])
        ) {
            throw new ReceiveException(sprintf(
                'Invalid receive message data. Data received: %s',
                var_export($data, true)
            ));
        }

        $receivedMessage = Message::create(
            $data['to'],
            $data['message'],
            $data['from']
        );

        $receivedMessage->setId($data['id']);

        return $receivedMessage;
    }

    /**
     * @throws ReceiveException
     */
    public function receiveDeliveryReport(array $data): DeliveryReportInterface
    {
        if (empty($data['id']) || empty($data['status'])) {
            throw new ReceiveException(sprintf(
                'Invalid message delivery report data. Data received: %s',
                var_export($data, true)
            ));
        }

        return new DeliveryReport($data['id'], $data['status']);
    }
}
