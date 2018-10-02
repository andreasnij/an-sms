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
use AnSms\Message\Address\AddressInterface;
use AnSms\Message\Address\Alphanumeric;
use AnSms\Message\Address\ShortCode;
use AnSms\Message\Message;
use AnSms\Message\MessageInterface;
use AnSms\Message\DeliveryReport\DeliveryReportInterface;
use AnSms\Message\DeliveryReport\DeliveryReport;
use AnSms\Message\PremiumMessageInterface;
use Http\Client\Exception\TransferException;
use Http\Client\HttpClient;
use Http\Message\MessageFactory;

/**
 * 46Elks SMS gateway provider.
 *
 * @author Jon Gotlin <http://github.com/jongotlin>
 */
class FortySixElksGateway extends AbstractHttpGateway implements GatewayInterface
{
    protected const SMS_API_ENDPOINT = 'https://api.46elks.com/a1/SMS';

    /**
     * @var string
     */
    protected $apiUsername;

    /**
     * @var string
     */
    protected $apiPassword;

    public function __construct(
        string $apiUsername,
        string $apiPassword,
        HttpClient $httpClient = null,
        MessageFactory $messageFactory = null
    ) {
        parent::__construct($httpClient, $messageFactory);

        if (empty($apiUsername) || empty($apiPassword)) {
            throw new \InvalidArgumentException('46 Elks api username and api password are required');
        }

        $this->apiUsername = $apiUsername;
        $this->apiPassword = $apiPassword;
    }

    /**
     * @param MessageInterface $message
     * @throws SendException
     */
    public function sendMessage(MessageInterface $message): void
    {
        $data = http_build_query($this->buildSendData($message));
        $request = $this->messageFactory->createRequest(
            'POST',
            self::SMS_API_ENDPOINT,
            $this->getHeaders(),
            $data
        );

        try {
            $response = $this->httpClient->sendRequest($request);
            $content = (string) $response->getBody();

            $this->parseSendResponseContent($content, $message);
        } catch (TransferException $e) {
            throw new SendException($e->getMessage(), 0, $e);
        }
    }

    protected function getHeaders(): array
    {
        $headers = [
            'Authorization' => 'Basic ' . base64_encode(sprintf('%s:%s', $this->apiUsername, $this->apiPassword)),
            'Content-type' => 'application/x-www-form-urlencoded',
        ];

        return $headers;
    }

    protected function buildSendData(MessageInterface $message): array
    {
        $data = [
            'from' => (string) $message->getFrom(),
            'to' => '+' . $message->getTo(),
            'message' => $message->getText(),
        ];

        return $data;
    }

    /**
     * @param string $content
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

    public function receiveMessage($data): MessageInterface
    {
        if (empty($data['id']) || empty($data['from']) || empty($data['to']) || empty($data['message'])) {
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

    public function receiveDeliveryReport($data): DeliveryReportInterface
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
