<?php

namespace Tests\Message\Address;

use AnSms\Message\Address\PhoneNumber;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    /**
     * @dataProvider createProvider
     */
    public function testCanPhoneNumberBeCreated(string $value, bool $expectException = false)
    {
        if ($expectException) {
            $this->expectException(\InvalidArgumentException::class);
        }

        $alphanumeric = new PhoneNumber($value);

        $this->assertSame($value, $alphanumeric->get());
        $this->assertSame($value, (string) $alphanumeric);
    }

    public function createProvider(): array
    {
        return [
            ['46700123456'],
            ['0700123456', true],
            ['12345', true],
            ['12345abc', true],
            ['46700123456789012345', true],
        ];
    }
}
