<?php declare(strict_types=1);

/**
 * An SMS library.
 *
 * @copyright Copyright (c) 2017 Andreas Nilsson
 * @license   MIT
 */

namespace AnSms;

use AnSms\Exception\ReceiveException;
use AnSms\Exception\SendException;
use AnSms\Message\Address\AddressInterface;
use AnSms\Message\DeliveryReport\DeliveryReportInterface;
use AnSms\Message\MessageInterface;
use Psr\Log\LoggerAwareInterface;

/**
 * Interface for sending and receiving SMS text messages.
 *
 * @author Andreas Nilsson <http://github.com/jandreasn>
 */
interface SmsTransceiverInterface extends LoggerAwareInterface
{
    /**
     * @param MessageInterface $message
     * @throws SendException
     */
    public function sendMessage(MessageInterface $message) : void;

    /**
     * @param MessageInterface[] $messages
     * @throws SendException
     */
    public function sendMessages(array $messages): void;

    /**
     * @param array $data
     * @return MessageInterface
     * @throws ReceiveException
     */
    public function receiveMessage(array $data) : MessageInterface;

    /**
     * @param array $data
     * @return DeliveryReportInterface
     * @throws ReceiveException
     */
    public function receiveDeliveryReport(array $data) : DeliveryReportInterface;

    /**
     * @param $defaultFrom AddressInterface|string|null
     */
    public function setDefaultFrom($defaultFrom): void;
}
