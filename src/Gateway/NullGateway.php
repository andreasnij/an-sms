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
 *
 * @author Andreas Nilsson <http://github.com/jandreasn>
 */
class NullGateway implements GatewayInterface
{
    /**
     * @param MessageInterface $message
     * @throws SendException
     */
    public function sendMessage(MessageInterface $message): void
    {
        $message->setId('Null gateway tracking id');
    }

    /**
     * @param MessageInterface[] $messages
     * @throws SendException
     */
    public function sendMessages(array $messages): void
    {
        foreach ($messages as $message) {
             $message->setId('Null gateway tracking id');
        }
    }

    /**
     * @param array $data
     * @throws ReceiveException
     * @return MessageInterface
     */
    public function receiveMessage(array $data): MessageInterface
    {
        return Message::create(
            $data['destination'] ?? '46700112233',
            $data['text'] ?? 'Null gateway text',
            $data['originator'] ?? '46700123456'
        );
    }

    /**
     * @param array $data
     * @throws ReceiveException
     * @return DeliveryReportInterface
     */
    public function receiveDeliveryReport(array $data): DeliveryReportInterface
    {
        return new DeliveryReport(
            $data['trackingid'] ?? 'Null gateway tracking id',
            $data['status'] ??  'Null gateway status'
        );
    }
}
