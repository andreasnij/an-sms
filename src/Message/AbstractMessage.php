<?php declare(strict_types=1);

/**
 * An SMS library.
 *
 * @copyright Copyright (c) 2017 Andreas Nilsson
 * @license   MIT
 */

namespace AnSms\Message;

use AnSms\Message\Address\AddressInterface;

/**
 * @author Andreas Nilsson <http://github.com/jandreasn>
 */
abstract class AbstractMessage implements MessageInterface
{
    /**
     * @var AddressInterface
     */
    protected $to;

    /**
     * @var string
     */
    protected $text;

    /**
     * @var AddressInterface|null
     */
    protected $from;

    /**
     * @var string|null
     */
    protected $id;

    /**
     * @var string|null
     */
    protected $operator;

    /**
     * @var string|null
     */
    protected $countryCode;

    /**
     * @var int|null
     */
    protected $segmentCount;

    /**
     * @param AddressInterface      $to   The recipient's number
     * @param string                $text The message text contents
     * @param AddressInterface|null $from The message sender's number/name
     */
    public function __construct(AddressInterface $to, string $text, AddressInterface $from = null)
    {
        $this->to = $to;
        $this->setText($text);
        $this->from = $from;
    }

    public function setTo(AddressInterface $to): void
    {
        $this->to = $to;
    }

    public function getTo(): AddressInterface
    {
        return $this->to;
    }

    public function setText(string $text): void
    {
        if (empty($text)) {
            throw new \InvalidArgumentException('Text is required');
        }

        $this->text = $text;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setFrom(?AddressInterface $from): void
    {
        $this->from = $from;
    }

    public function getFrom(): ?AddressInterface
    {
        return $this->from;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setOperator(string $operator): void
    {
        $this->operator = $operator;
    }

    public function getOperator(): ?string
    {
        return $this->operator;
    }

    public function setCountryCode(string $countryCode): void
    {
        $this->countryCode = $countryCode;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function getSegmentCount(): ?int
    {
        return $this->segmentCount;
    }

    public function setSegmentCount(int $segmentCount): void
    {
        $this->segmentCount = $segmentCount;
    }

    public function getLogContext(): array
    {
        return array_filter([
            'to' => (string) $this->getTo(),
            'text' =>  $this->getText(),
            'from' => (string) $this->getFrom(),
            'id' => $this->getId(),
            'operator' => $this->getOperator(),
            'countryCode' => $this->getCountryCode(),
            'segmentCount' => $this->getSegmentCount(),
        ]);
    }
}
