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
class PhoneNumber extends AbstractAddress
{
    /**
     * @param string $phoneNumber A mobile telephone number in MSISDN/E.164 format
     *                            (example: "46701223344", where "46" is the country code)
     *                            (http://en.wikipedia.org/wiki/MSISDN).
     */
    public function __construct(string $phoneNumber)
    {
        if (!preg_match('/^[1-9][0-9]{7,14}$/', $phoneNumber)) {
            throw new \InvalidArgumentException("{$phoneNumber} is not a valid MSISDN phone number");
        }

        $this->value = $phoneNumber;
    }
}
