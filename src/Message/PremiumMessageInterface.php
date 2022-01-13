<?php declare(strict_types=1);

/**
 * An SMS library.
 *
 * @copyright Copyright (c) 2017 Andreas Nilsson
 * @license   MIT
 */

namespace AnSms;

namespace AnSms\Message;

/**
 * Interface for a premium SMS text message.
 */
interface PremiumMessageInterface extends MessageInterface
{
    public function getPrice(): int;

    public function getIncomingMessageId(): string;
}
