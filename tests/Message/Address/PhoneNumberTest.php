<?php

namespace Tests\Message\Address;

use AnSms\Message\Address\PhoneNumber;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    /**
     * @dataProvider createProvider
     */
    public function testCanPhoneNumberBeCreated(string $testPhoneNumber, bool $valid, string $expectedResult = null)
    {
        if (! $valid) {
            $this->expectException(\InvalidArgumentException::class);
        }

        $phoneNumber = new PhoneNumber($testPhoneNumber);

        $this->assertSame($expectedResult, $phoneNumber->get());
        $this->assertSame($expectedResult, (string) $phoneNumber);
    }

    public function createProvider(): array
    {
        return [
            ['46700123456', true, '46700123456'],
            ['+46700123456', true, '46700123456'],
            ['0700123456', false],
            ['12345', false],
            ['12345abc', false],
            ['46700123456789012345', false],
        ];
    }
}
