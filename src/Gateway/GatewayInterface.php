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
use AnSms\Message\MessageInterface;
use AnSms\Message\DeliveryReport\DeliveryReportInterface;

interface GatewayInterface
{
    /**
     * @param MessageInterface $message
     * @throws SendException
     */
    public function sendMessage(MessageInterface $message): void;

    /**
     * @param MessageInterface[] $messages
     * @throws SendException
     */
    public function sendMessages(array $messages): void;

    /**
     * @param mixed $data
     * @throws ReceiveException
     * @return MessageInterface
     */
    public function receiveMessage($data): MessageInterface;

    /**
     * @param mixed $data
     * @throws ReceiveException
     * @return DeliveryReportInterface
     */
    public function receiveDeliveryReport($data): DeliveryReportInterface;
}
