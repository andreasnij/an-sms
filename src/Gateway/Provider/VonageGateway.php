<?php declare(strict_types=1);

/**
 * An SMS library.
 *
 * @copyright Copyright (c) 2017 Andreas Nilsson
 * @license   MIT
 */

namespace AnSms\Gateway\Provider;

use AnSms\Gateway\GatewayInterface;
use AnSms\Exception\ReceiveException;
use AnSms\Exception\SendException;
use AnSms\Message\Message;
use AnSms\Message\MessageInterface;
use AnSms\Message\DeliveryReport\DeliveryReportInterface;
use AnSms\Message\DeliveryReport\DeliveryReport;
use InvalidArgumentException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Vonage\Client\Credentials\Basic as VonageBasicCredentials;
use Vonage\Client as VonageClient;
use Vonage\Client\Exception\Exception as VonageClientException;

/**
 * Vonage SMS gateway provider.
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

        $this->vonageClient = $vonageClient;
        if ($vonageClient === null) {
            $credentials = new VonageBasicCredentials($apiKey, $apiSecret);
            $this->vonageClient = new VonageClient($credentials, [], $httpClient);
        }
    }

    public function getVonageClient(): VonageClient
    {
        return $this->vonageClient;
    }

    /**
     * @param MessageInterface $message
     * @throws SendException
     */
    public function sendMessage(MessageInterface $message): void
    {
        try {
            $vonageMessage = $this->vonageClient->message()->send([
                'to' => $message->getTo(),
                'text' => $message->getText(),
                'from' => $message->getFrom(),
            ]);

            $message->setId($vonageMessage->getMessageId());
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
     * {@inheritdoc}
     */
    public function receiveMessage($data): MessageInterface
    {
        if (empty($data['text']) || empty($data['to']) || empty($data['msisdn']) || empty($data['messageId'])) {
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
     * {@inheritdoc}
     */
    public function receiveDeliveryReport($data): DeliveryReportInterface
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
