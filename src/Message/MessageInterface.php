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
 * Interface for a SMS text message.
 */
interface MessageInterface
{
    public function getTo(): AddressInterface;

    public function getText(): string;

    public function getFrom(): ?AddressInterface;

    public function setTo(AddressInterface $to): void;

    public function setText(string $text): void;

    public function setFrom(?AddressInterface $from): void;

    public function getLogContext(): array;

    public function setId(string $id): void;

    public function getId(): ?string;

    public function setOperator(string $operator): void;

    public function getOperator(): ?string;

    public function setCountryCode(string $countryCode): void;

    public function getCountryCode(): ?string;

    public function setSegmentCount(int $segmentCount): void;

    public function getSegmentCount(): ?int;
}
