<?php

namespace AnSms\Tests\Message\Address;

use AnSms\Message\Address\Alphanumeric;
use PHPUnit\Framework\TestCase;

class AlphanumericTest extends TestCase
{
    /**
     * @dataProvider alphanumericDataProvider
     */
    public function testCanAlphanumericBeCreated(string $value, bool $expectException = false): void
    {
        if ($expectException) {
            $this->expectException(\InvalidArgumentException::class);
        }

        $alphanumeric = new Alphanumeric($value);

        $this->assertSame($value, $alphanumeric->get());
        $this->assertSame($value, (string) $alphanumeric);
    }

    public static function alphanumericDataProvider(): array
    {
        return [
            ['abc123'],
            ['123'],
            ['abc'],
            ['ABC'],
            ['abc123[]', true],
            ['()', true],
            ['abcdefghijklmnopq', true],
            ['', true],
        ];
    }
}
