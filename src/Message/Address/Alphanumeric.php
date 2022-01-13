<?php declare(strict_types=1);

/**
 * An SMS library.
 *
 * @copyright Copyright (c) 2017 Andreas Nilsson
 * @license   MIT
 */

namespace AnSms\Message\Address;

class Alphanumeric extends AbstractAddress
{
    public function __construct(string $alphanumeric)
    {
        if (!preg_match('/^[A-Za-z0-9]{1,11}$/', $alphanumeric)) {
            throw new \InvalidArgumentException(
                'Alphanumeric originator should be 1 - 11 characters of A-Z, a-z or 0-9'
            );
        }

        $this->value = $alphanumeric;
    }
}
