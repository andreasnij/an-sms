<?php

namespace AnSms\Tests\Message\Address;

use AnSms\Message\Address\ShortCode;
use PHPUnit\Framework\TestCase;

class ShortCodeTest extends TestCase
{
    /**
     * @dataProvider shortCodeDataProvider
     */
    public function testCanShortCodeBeCreated(string $value, bool $expectException = false): void
    {
        if ($expectException) {
            $this->expectException(\InvalidArgumentException::class);
        }

        $alphanumeric = new ShortCode($value);

        $this->assertSame($value, $alphanumeric->get());
        $this->assertSame($value, (string) $alphanumeric);
    }

    public static function shortCodeDataProvider(): array
    {
        return [
            ['12345'],
            ['1', true],
            ['012345', true],
            ['01234567890', true],
        ];
    }
}
