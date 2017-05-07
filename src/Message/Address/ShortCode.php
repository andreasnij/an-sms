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
class ShortCode extends AbstractAddress
{
    /**
     * @param string $shortCode A telephone short code (example: "12345").
     */
    public function __construct(string $shortCode)
    {
        if (!preg_match('/^[1-9][0-9]{2,7}$/', $shortCode)) {
            throw new \InvalidArgumentException("{$shortCode} is not a valid short code");
        }

        $this->value = $shortCode;
    }
}
