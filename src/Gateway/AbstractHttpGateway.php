<?php declare(strict_types=1);

/**
 * An SMS library.
 *
 * @copyright Copyright (c) 2017 Andreas Nilsson
 * @license   MIT
 */

namespace AnSms\Gateway;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

abstract class AbstractHttpGateway
{
    protected ClientInterface $httpClient;
    protected RequestFactoryInterface $requestFactory;
    protected StreamFactoryInterface $streamFactory;

    /**
     * @param ClientInterface|null $httpClient Client to do HTTP requests.
     *                                         Auto discovery will be used if not provided.
     * @param RequestFactoryInterface|null $requestFactory Factory to create PSR-7 http request.
     *                                                     Auto discovery will be used if not provided.
     */
    public function __construct(
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
    ) {
        $this->httpClient = $httpClient ?? Psr18ClientDiscovery::find();
        $this->requestFactory = $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory();
        $this->streamFactory = $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory();
    }

    public function getHttpClient(): ClientInterface
    {
        return $this->httpClient;
    }

    public function getRequestFactory(): RequestFactoryInterface
    {
        return $this->requestFactory;
    }

    public function getStreamFactory(): StreamFactoryInterface
    {
        return $this->streamFactory;
    }
}
