<?php declare(strict_types=1);

/**
 * An SMS library.
 *
 * @copyright Copyright (c) 2017 Andreas Nilsson
 * @license   MIT
 */

namespace AnSms\Message\Address;

class Factory
{
    public static function create(string $address): AddressInterface
    {
        if (self::isShortCode($address)) {
            return new ShortCode($address);
        }

        return new PhoneNumber($address);
    }

    public static function createWithAlphanumeric(string $address): AddressInterface
    {
        if (self::isAlphanumeric($address)) {
            return new Alphanumeric($address);
        }

        return self::create($address);
    }

    protected static function isShortCode(string $address): bool
    {
        return (strlen($address) < 8);
    }

    protected static function isAlphanumeric(string $address): bool
    {
        return (bool) preg_match('/[A-Za-z]/', $address);
    }
}
