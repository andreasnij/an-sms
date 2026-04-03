<?php

namespace AnSms\Tests\Message;

use AnSms\Message\Address\AddressInterface;
use AnSms\Message\MessageInterface;
use AnSms\Message\PremiumMessage;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

class PremiumMessageTest extends TestCase
{
    public function testPremiumMessageCanBeCreated(): void
    {
        $to = '46700123456';
        $text = 'Thank you!';
        $price = 5;
        $incomingMessageId = '123';
        $from = '12345';
        $premiumMessage = PremiumMessage::create($to, $text, $price, $incomingMessageId, $from);

        $this->assertSame($to, (string) $to);
        $this->assertSame($text, $premiumMessage->getText());
        $this->assertSame($price, $premiumMessage->getPrice());
        $this->assertSame($incomingMessageId, $premiumMessage->getIncomingMessageId());
        $this->assertSame($from, (string) $premiumMessage->getFrom());
    }

    public function testPremiumMessageCanBeCreatedFromIncomingMessage(): void
    {
        $price = 5;
        $text = 'Thank you!';
        $incomingMessage = $this->createIncomingMessageStub();
        $premiumMessage = PremiumMessage::createFromIncomingMessage($text, $price, $incomingMessage);

        $this->assertSame($price, $premiumMessage->getPrice());
        $this->assertSame($text, $premiumMessage->getText());
        $this->assertSame($incomingMessage->getId(), $premiumMessage->getIncomingMessageId());
    }

    /**
     * @return MessageInterface&Stub
     */
    private function createIncomingMessageStub(): Stub
    {
        $incomingMessageStub = $this->createStub(MessageInterface::class);
        $incomingMessageStub->method('getTo')->willReturn($this->createStub(AddressInterface::class));
        $incomingMessageStub->method('getFrom')->willReturn($this->createStub(AddressInterface::class));
        $incomingMessageId = '123';
        $incomingMessageStub->method('getId')->willReturn($incomingMessageId);

        return $incomingMessageStub;
    }

    public function testLogContext(): void
    {
        $price = 5;
        $premiumMessage = PremiumMessage::createFromIncomingMessage(
            'Thank you!',
            $price,
            $this->createIncomingMessageStub()
        );

        $this->assertSame($price, $premiumMessage->getLogContext()['price'] ?? null);
    }
}
