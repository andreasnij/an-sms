<?php declare(strict_types=1);

/**
 * An SMS library.
 *
 * @copyright Copyright (c) 2017 Andreas Nilsson
 * @license   MIT
 */

namespace AnSms\Message\Address;

/**
 * @author Andreas Nilsson <http://github.com/jandreasn>
 */
interface AddressInterface
{
    public function get(): string;

    public function __toString(): string;
}
