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
use AnSms\Gateway\GatewayInterface;
use AnSms\Message\Address\AddressInterface;
use AnSms\Message\Address\Factory as AddressFactory;
use AnSms\Message\DeliveryReport\DeliveryReportInterface;
use AnSms\Message\MessageInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Send and receives SMS text messages.
 */
class SmsTransceiver implements SmsTransceiverInterface
{
    use LoggerAwareTrait;

    protected GatewayInterface $gateway;
    protected ?AddressInterface $defaultFrom = null;

    public function __construct(GatewayInterface $gateway, ?LoggerInterface $logger = null)
    {
        $this->gateway = $gateway;

        if ($logger) {
            $this->setLogger($logger);
        }
    }

    /**
     * @param MessageInterface $message
     * @throws SendException
     */
    public function sendMessage(MessageInterface $message): void
    {
        $this->setMessageFrom($message);

        $this->gateway->sendMessage($message);

        if ($this->logger) {
            $this->logger->info('SMS sent', $message->getLogContext());
        }
    }

    /**
     * @param MessageInterface[] $messages
     * @throws SendException
     */
    public function sendMessages(array $messages): void
    {
        foreach ($messages as $message) {
            $this->setMessageFrom($message);
        }

        $this->gateway->sendMessages($messages);

        if ($this->logger) {
            foreach ($messages as $message) {
                $this->logger->info('SMS sent', $message->getLogContext());
            }
        }
    }

    /**
     * @throws ReceiveException
     */
    public function receiveMessage(mixed $data): MessageInterface
    {
        $message = $this->gateway->receiveMessage($data);

        if ($this->logger) {
            $this->logger->info('SMS received', $message->getLogContext());
        }

        return $message;
    }

    /**
     * @throws ReceiveException
     */
    public function receiveDeliveryReport(mixed $data): DeliveryReportInterface
    {
        $deliveryReport = $this->gateway->receiveDeliveryReport($data);

        if ($this->logger) {
            $this->logger->info('SMS delivery report received', $deliveryReport->getLogContext());
        }

        return $deliveryReport;
    }

    public function setDefaultFrom(AddressInterface|string|null $defaultFrom): void
    {
        $this->defaultFrom = null;
        if ($defaultFrom instanceof AddressInterface) {
            $this->defaultFrom = $defaultFrom;
        } elseif (is_string($defaultFrom)) {
            $this->defaultFrom = AddressFactory::createWithAlphanumeric($defaultFrom);
        }
    }

    protected function setMessageFrom(MessageInterface $message): void
    {
        if ($this->defaultFrom !== null && $message->getFrom() === null) {
            $message->setFrom($this->defaultFrom);
        }
    }
}
