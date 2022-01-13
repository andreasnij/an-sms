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
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client as TwilioClient;
use Twilio\Values;

/**
 * Twilio SMS gateway.
 */
class TwilioGateway implements GatewayInterface
{
    protected TwilioClient $twilioClient;

    public function __construct(
        string $accountSid,
        string $authToken,
        ?TwilioClient $twilioClient = null
    ) {
        if (empty($accountSid) || empty($authToken)) {
            throw new InvalidArgumentException('Twilio Account SID and auth token are required');
        }


        if ($twilioClient) {
            $this->twilioClient = $twilioClient;
        } else {
            $this->twilioClient = new TwilioClient($accountSid, $authToken);
        }
    }

    public function getTwilioClient(): TwilioClient
    {
        return $this->twilioClient;
    }

    /**
     * @throws SendException
     */
    public function sendMessage(MessageInterface $message): void
    {
        try {
            $twilioMessage =  $this->twilioClient->messages->create(
                (string) $message->getTo(),
                [
                    'from' => $message->getFrom() ? (string) $message->getFrom() : Values::NONE,
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
     * @throws ReceiveException
     */
    public function receiveMessage(array $data): MessageInterface
    {
        if (empty($data['To']) || empty($data['Body'])
            || empty($data['From']) || empty($data['MessageSid'])
        ) {
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
     * @throws ReceiveException
     */
    public function receiveDeliveryReport(array $data): DeliveryReportInterface
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
