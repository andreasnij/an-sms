<?php declare(strict_types=1);

/**
 * An SMS library.
 *
 * @copyright Copyright (c) 2017 Andreas Nilsson
 * @license   MIT
 */

namespace AnSms\Gateway;

use Http\Client\HttpClient;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Message\MessageFactory;

abstract class AbstractHttpGateway
{
    /**
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * @var MessageFactory
     */
    protected $messageFactory;

   /**
     * @param HttpClient|null     $httpClient     Client to do HTTP requests.
     *                                            Auto discovery will be used if not provided.
     * @param MessageFactory|null $messageFactory Factory to create PSR-7 http messages.
     *                                            Auto discovery will be used if not provided.
     */
    public function __construct(HttpClient $httpClient = null, MessageFactory $messageFactory = null)
    {
        $this->httpClient = $httpClient ?: HttpClientDiscovery::find();
        $this->messageFactory = $messageFactory ?: MessageFactoryDiscovery::find();
    }

    public function getHttpClient(): HttpClient
    {
        return $this->httpClient;
    }

    public function getMessageFactory(): MessageFactory
    {
        return $this->messageFactory;
    }
}
