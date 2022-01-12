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
use Vonage\Client as VonageClient;
use Vonage\Client\Credentials\Basic as VonageBasicCredentials;
use Vonage\Client\Exception\Exception as VonageClientException;

/**
 * Vonage SMS gateway.
 */
class VonageGateway implements GatewayInterface
{
    protected VonageClient $vonageClient;

    public function __construct(
        string $apiKey,
        string $apiSecret,
        ?VonageClient $vonageClient = null,
        ?ClientInterface $httpClient = null,
    ) {
        if (empty($apiKey) || empty($apiSecret)) {
            throw new InvalidArgumentException('Vonage API key and secret are required');
        }


        if ($vonageClient) {
            $this->vonageClient = $vonageClient;
        } else {
            $credentials = new VonageBasicCredentials($apiKey, $apiSecret);
            $this->vonageClient = new VonageClient($credentials, [], $httpClient);
        }
    }

    public function getVonageClient(): VonageClient
    {
        return $this->vonageClient;
    }

    /**
     * @throws SendException
     */
    public function sendMessage(MessageInterface $message): void
    {
        try {
            $vonageMessage = $this->vonageClient->message()->send([
                'to' => (string) $message->getTo(),
                'text' => $message->getText(),
                'from' => $message->getFrom() ? (string) $message->getFrom() : null,
            ]);

            if (($messageId = $vonageMessage->getMessageId())) {
                $message->setId($messageId);
            }
        } catch (ClientExceptionInterface | VonageClientException $e) {
            throw new SendException($e->getMessage(), 0, $e);
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
        if (empty($data['text']) || empty($data['to'])
            || empty($data['msisdn']) || empty($data['messageId'])
        ) {
            throw new ReceiveException(sprintf(
                'Invalid receive message data. Data received: %s',
                var_export($data, true)
            ));
        }

        $receivedMessage = Message::create(
            $data['to'],
            trim($data['text']),
            $data['msisdn']
        );

        $receivedMessage->setId($data['messageId']);

        return $receivedMessage;
    }

    /**
     * @throws ReceiveException
     */
    public function receiveDeliveryReport(array $data): DeliveryReportInterface
    {
        if (empty($data['messageId']) || empty($data['status'])) {
            throw new ReceiveException(sprintf(
                'Invalid message delivery report data. Data received: %s',
                var_export($data, true)
            ));
        }

        return new DeliveryReport($data['messageId'], $data['status']);
    }
}
