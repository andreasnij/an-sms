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

/**
 * Black hole gateway.
 *
 * Can be used for development/testing.
 */
class NullGateway implements GatewayInterface
{
    /**
     * @throws SendException
     */
    public function sendMessage(MessageInterface $message): void
    {
    }

    /**
     * @param MessageInterface[] $messages
     * @throws SendException
     */
    public function sendMessages(array $messages): void
    {
    }

    /**
     * @throws ReceiveException
     */
    public function receiveMessage(mixed $data): MessageInterface
    {
        if (!is_array($data)) {
            throw new ReceiveException(sprintf(
                'Invalid receive message data. Data received: %s',
                var_export($data, true)
            ));
        }

        $message = Message::create(
            $data['to'] ?? '46700000000',
            $data['text'] ?? '-',
            array_key_exists('from', $data) ? $data['from'] : '46700111111'
        );

        $message->setId(uniqid());

        return $message;
    }

    /**
     * @throws ReceiveException
     */
    public function receiveDeliveryReport(mixed $data): DeliveryReportInterface
    {
        if (!is_array($data)) {
            throw new ReceiveException(sprintf(
                'Invalid message delivery report data. Data received: %s',
                var_export($data, true)
            ));
        }

        return new DeliveryReport(
            $data['trackingid'] ?? '',
            $data['status'] ??  ''
        );
    }
}
