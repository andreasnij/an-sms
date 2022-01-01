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
use Twilio\Rest\Client as TwilioClient;
use Twilio\Exceptions\TwilioException;
use Twilio\Values;

/**
 * Twilio SMS gateway provider.
 */
class TwilioGateway implements GatewayInterface
{
    /** @var TwilioClient $twilioClient */
    protected $twilioClient;

    public function __construct(
        string $accountSid,
        string $authToken,
        TwilioClient $twilioClient = null
    ) {
        if (empty($accountSid) || empty($authToken)) {
            throw new \InvalidArgumentException('Twilio Account SID and auth token are required');
        }

        $this->twilioClient = $twilioClient;
        if ($twilioClient === null) {
            $this->twilioClient = new TwilioClient($accountSid, $authToken);
        }
    }

    public function getTwilioClient(): TwilioClient
    {
        return $this->twilioClient;
    }

    /**
     * @param MessageInterface $message
     * @throws SendException
     */
    public function sendMessage(MessageInterface $message): void
    {
        try {
            $twilioMessage =  $this->twilioClient->messages->create(
                $message->getTo(),
                [
                    'from' => $message->getFrom() ?: Values::NONE,
                    'body' => $message->getText(),
                ]
            );

            $message->setId($twilioMessage->sid);
        } catch (TwilioException $e) {
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
        if (empty($data['To']) || empty($data['Body']) || empty($data['From']) || empty($data['MessageSid'])) {
            throw new ReceiveException(sprintf(
                'Invalid receive message data. Data received: %s',
                var_export($data, true)
            ));
        }

        $receivedMessage = Message::create(
            $data['To'],
            trim($data['Body']),
            $data['From']
        );

        $receivedMessage->setId($data['MessageSid']);

        return $receivedMessage;
    }

    /**
     * {@inheritdoc}
     */
    public function receiveDeliveryReport($data): DeliveryReportInterface
    {
        if (empty($data['MessageSid']) || empty($data['MessageStatus'])) {
            throw new ReceiveException(sprintf(
                'Invalid message delivery report data. Data received: %s',
                var_export($data, true)
            ));
        }

        return new DeliveryReport($data['MessageSid'], $data['MessageStatus']);
    }
}
