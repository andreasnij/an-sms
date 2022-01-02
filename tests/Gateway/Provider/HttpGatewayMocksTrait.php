<?php

namespace AnSms\Tests\Gateway\Provider;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

trait HttpGatewayMocksTrait
{
    private ClientInterface|MockObject $clientMock;
    private RequestFactoryInterface|MockObject $requestFactoryMock;
    private StreamFactoryInterface|MockObject $streamFactoryMock;

    protected function createHttpGatewayMocks(): void
    {
        $this->clientMock = $this->createMock(ClientInterface::class);
        $this->requestFactoryMock = $this->createMock(RequestFactoryInterface::class);
        $this->streamFactoryMock = $this->createMock(StreamFactoryInterface::class);
    }
}
