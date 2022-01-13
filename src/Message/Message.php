<?php declare(strict_types=1);

/**
 * An SMS library.
 *
 * @copyright Copyright (c) 2017 Andreas Nilsson
 * @license   MIT
 */

namespace AnSms\Message;

use AnSms\Message\Address\Factory as AddressFactory;

/**
 * Represents a SMS text message.
 */
class Message extends AbstractMessage
{
    public static function create(string $to, string $text, ?string $from = null): self
    {
        return new self(
            AddressFactory::create($to),
            $text,
            ($from !== null) ? AddressFactory::createWithAlphanumeric($from) : null
        );
    }
}
