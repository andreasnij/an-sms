<?php

namespace AnSms\Tests\Gateway;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

trait HttpGatewayMocksTrait
{
    /** @var ClientInterface&MockObject  */
    private MockObject $clientMock;

    /** @var RequestFactoryInterface&MockObject  */
    private MockObject $requestFactoryMock;

    /** @var StreamFactoryInterface&MockObject  */
    private MockObject $streamFactoryMock;

    protected function createHttpGatewayMocks(): void
    {
        $this->clientMock = $this->createMock(ClientInterface::class);
        $this->requestFactoryMock = $this->createMock(RequestFactoryInterface::class);
        $this->streamFactoryMock = $this->createMock(StreamFactoryInterface::class);
    }
}
