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
    protected $username;

    /**
     * @var string
     */
    protected $password;

    public function __construct(
        string $username,
        string $password,
        HttpClient $httpClient = null,
        MessageFactory $messageFactory = null
    ) {
        parent::__construct($httpClient, $messageFactory);

        if (empty($username) || empty($password)) {
            throw new \InvalidArgumentException('46 Elks username and password are required');
        }

        $this->username = $username;
        $this->password = $password;
    }

    /**
     * @param MessageInterface $message
     * @throws SendException
     */
    public function sendMessage(MessageInterface $message): void
    {
        $queryData = $this->buildSendQueryData($message);
        $query = http_build_query($queryData);
        $request = $this->messageFactory->createRequest(
            'POST',
            $this->getApiEndpoint(),
            $this->getHeaders(),
            $query
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

    protected function getHeaders(): array
    {
        $headers = [
            'Authorization' => 'Basic ' . base64_encode(sprintf('%s:%s', $this->username, $this->password)),
            'Content-type' => 'application/x-www-form-urlencoded',
        ];

        return $headers;
    }

    protected function buildSendQueryData(MessageInterface $message): array
    {
        $queryData = [
            'from' => (string) $message->getFrom(),
            'to' => '+' . $message->getTo(),
            'message' => $message->getText(),
        ];

        return $queryData;
    }

    protected function getApiEndpoint(): string
    {
        return static::SMS_API_ENDPOINT;
    }

    /**
     * @param string $content
     * @throws SendException
     * @return string
     */
    protected function parseSendResponseContent(string $content): string
    {
        $result = json_decode($content, true);
        if (!is_array($result)) {
            throw new SendException('Send message failed with error: ' . $content);
        } elseif (!isset($result['status']) || !in_array($result['status'], ['created', 'sent', 'delivered'])) {
            throw new SendException('Send message failed with missing status value: ' . $content);
        } elseif (!isset($result['id'])) {
            throw new SendException('Message sent but missing id in response: ' . $content);
        }

        return $result['id'];
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
        // @todo
    }

    public function receiveDeliveryReport($data): DeliveryReportInterface
    {
        // @todo
    }
}
