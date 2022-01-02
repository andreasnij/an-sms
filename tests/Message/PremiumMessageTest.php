<?php

namespace AnSms\Tests\Message;

use AnSms\Message\Address\AddressInterface;
use AnSms\Message\MessageInterface;
use AnSms\Message\PremiumMessage;
use PHPUnit\Framework\MockObject\MockObject;
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
        $incomingMessage = $this->createIncomingMessageMock();
        $premiumMessage = PremiumMessage::createFromIncomingMessage($text, $price, $incomingMessage);

        $this->assertSame($price, $premiumMessage->getPrice());
        $this->assertSame($text, $premiumMessage->getText());
        $this->assertSame($incomingMessage->getId(), $premiumMessage->getIncomingMessageId());
    }

    /**
     * @return MessageInterface&MockObject
     */
    private function createIncomingMessageMock(): MockObject
    {
        $incomingMessageMock = $this->createMock(MessageInterface::class);
        $incomingMessageMock->method('getTo')->willReturn($this->createMock(AddressInterface::class));
        $incomingMessageMock->method('getFrom')->willReturn($this->createMock(AddressInterface::class));
        $incomingMessageId = '123';
        $incomingMessageMock->method('getId')->willReturn($incomingMessageId);

        return $incomingMessageMock;
    }

    public function testLogContext(): void
    {
        $price = 5;
        $premiumMessage = PremiumMessage::createFromIncomingMessage(
            'Thank you!',
            $price,
            $this->createIncomingMessageMock()
        );

        $this->assertSame($price, $premiumMessage->getLogContext()['price'] ?? null);
    }
}
