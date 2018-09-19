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
use AnSms\Message\Message;
use AnSms\Message\MessageInterface;
use AnSms\Message\DeliveryReport\DeliveryReportInterface;
use AnSms\Message\DeliveryReport\DeliveryReport;
use Http\Client\Exception\TransferException;
use Http\Client\HttpClient;
use Http\Message\MessageFactory;
use Nexmo\Client\Credentials\Basic as NexmoBasicCredentials;
use Nexmo\Client as NexmoClient;
use Nexmo\Client\Exception\Exception as NexmoClientException;

/**
 * Nexmo SMS gateway provider.
 *
 * @author Andreas Nilsson <http://github.com/jandreasn>
 */
class NexmoGateway extends AbstractHttpGateway implements GatewayInterface
{
    /** @var NexmoClient $nexmoClient */
    protected $nexmoClient;

    public function __construct(
        string $apiKey,
        string $apiSecret,
        HttpClient $httpClient = null,
        MessageFactory $messageFactory = null,
        NexmoClient $nexmoClient = null
    ) {
        parent::__construct($httpClient, $messageFactory);

        if (empty($apiKey) || empty($apiSecret)) {
            throw new \InvalidArgumentException('Nexmo API key and secret are required');
        }

        $this->nexmoClient = $nexmoClient;
        if ($nexmoClient === null) {
            $credentials = new NexmoBasicCredentials($apiKey, $apiSecret);
            $this->nexmoClient = new NexmoClient($credentials);
        }
    }

    public function getNexmoClient(): NexmoClient
    {
        return $this->nexmoClient;
    }

    /**
     * @param MessageInterface $message
     * @throws SendException
     */
    public function sendMessage(MessageInterface $message): void
    {
        try {
            $nexmoMessage = $this->nexmoClient->message()->send([
                'to' => $message->getTo(),
                'text' => $message->getText(),
                'from' => $message->getFrom(),
            ]);

            $message->setId($nexmoMessage->getMessageId());
        } catch (TransferException | NexmoClientException $e) {
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
