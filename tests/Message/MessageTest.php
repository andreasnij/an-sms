<?php

namespace AnSms\Tests\Message;

use AnSms\Message\Address\PhoneNumber;
use AnSms\Message\Message;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    private const TEST_TO = '46700123456';
    private const TEST_TEXT = 'Hello world!';
    private const TEST_FROM = 'Tester';
    private const TEST_ID = '12345';
    private const TEST_OPERATOR = 'Company';
    private const TEST_COUNTRY_CODE = 'SE';
    private const SEGMENT_COUNT = 1;

    /**
     * @var Message
     */
    private $message;

    protected function setUp(): void
    {
        $this->message = Message::create(
            self::TEST_TO,
            self::TEST_TEXT,
            self::TEST_FROM
        );

        $this->message->setId(self::TEST_ID);
        $this->message->setOperator(self::TEST_OPERATOR);
        $this->message->setCountryCode(self::TEST_COUNTRY_CODE);
        $this->message->setSegmentCount(self::SEGMENT_COUNT);
    }

    public function testMessageCanBeCreated()
    {
        $this->assertSame(self::TEST_TO, (string) $this->message->getTo());
        $this->assertSame(self::TEST_TEXT, $this->message->getText());
        $this->assertSame(self::TEST_FROM, (string) $this->message->getFrom());
    }

    public function testToCanBeSet()
    {
        $newTo = new PhoneNumber('46700123123');
        $this->message->setTo($newTo);

        $this->assertSame((string) $newTo, (string) $this->message->getTo());
    }

    public function testTextCanBeSet()
    {
        $newText = 'Helloooo!';
        $this->message->setText($newText);

        $this->assertSame($newText, $this->message->getText());
    }

    public function testFromCanBeSet()
    {
        $newFrom = new PhoneNumber('46700123456');
        $this->message->setFrom($newFrom);

        $this->assertSame((string) $newFrom, (string) $this->message->getFrom());
    }

    public function testIdCanBeSet()
    {
        $newId = '54321';
        $this->message->setId($newId);

        $this->assertSame($newId, $this->message->getId());
    }

    public function testOperatorCanBeSet()
    {
        $newOperator = 'Other Company';
        $this->message->setOperator($newOperator);

        $this->assertSame($newOperator, $this->message->getOperator());
    }

    public function testCountryCodeCanBeSet()
    {
        $newCountryCode = 'UK';
        $this->message->setCountryCode($newCountryCode);

        $this->assertSame($newCountryCode, $this->message->getCountryCode());
    }

    public function testLogContext()
    {
         $this->assertSame(
             [
                 'to' => self::TEST_TO,
                 'text' => self::TEST_TEXT,
                 'from' => self::TEST_FROM,
                 'id' => self::TEST_ID,
                 'operator' => self::TEST_OPERATOR,
                 'countryCode' => self::TEST_COUNTRY_CODE,
                 'segmentCount' => self::SEGMENT_COUNT,
             ],
             $this->message->getLogContext()
         );
    }
}
