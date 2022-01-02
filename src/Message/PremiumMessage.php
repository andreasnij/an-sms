<?php declare(strict_types=1);

/**
 * An SMS library.
 *
 * @copyright Copyright (c) 2017 Andreas Nilsson
 * @license   MIT
 */

namespace AnSms\Message;

use AnSms\Message\Address\AddressInterface;
use AnSms\Message\Address\Factory as AddressFactory;
use InvalidArgumentException;

/**
 * Represents a premium mobile terminated (MT) reverse billing outgoing SMS message.
 */
class PremiumMessage extends AbstractMessage implements PremiumMessageInterface
{
    protected int $price;
    protected string $incomingMessageId;

    /**
     * @param AddressInterface      $to                The recipient's number
     * @param string                $text              The message text contents
     * @param int                   $price             The price to charge the recipient
     * @param string                $incomingMessageId The unique identifier of the incoming message
     * @param AddressInterface|null $from              The message sender's number
     */
    public function __construct(
        AddressInterface $to,
        string $text,
        int $price,
        string $incomingMessageId,
        ?AddressInterface $from = null
    ) {
        parent::__construct($to, $text, $from);

        $this->price = $price;
        $this->incomingMessageId = $incomingMessageId;
    }

    public static function create(
        string $to,
        string $text,
        int $price,
        string $incomingMessageId,
        ?string $from = null
    ): self {
        return new self(
            AddressFactory::create($to),
            $text,
            $price,
            $incomingMessageId,
            ($from !== null) ? AddressFactory::createWithAlphanumeric($from) : null
        );
    }

    public static function createFromIncomingMessage(
        string $text,
        int $price,
        MessageInterface $incomingMessage
    ): self {
        if (! ($from = $incomingMessage->getFrom())) {
            throw new InvalidArgumentException('Incoming message has empty from');
        }

        if (! ($incomingMessageId = $incomingMessage->getId())) {
            throw new InvalidArgumentException('Incoming message has empty id');
        }

        return new self(
            $from,
            $text,
            $price,
            $incomingMessageId,
            $incomingMessage->getTo()
        );
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function getIncomingMessageId(): string
    {
        return $this->incomingMessageId;
    }

    public function getLogContext(): array
    {
        return parent::getLogContext() + array_filter([
            'price' => $this->getPrice(),
            'incomingMessageId' => $this->getIncomingMessageId()
        ]);
    }
}
