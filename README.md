# AnSms - A PHP SMS library

[![Version](http://img.shields.io/packagist/v/jandreasn/an-sms.svg?style=flat-square)](https://packagist.org/packages/jandreasn/an-sms)
[![Build Status](https://travis-ci.org/jandreasn/an-sms.svg?branch=master)](https://travis-ci.org/jandreasn/an-sms)

An extendable library for sending and receiving SMS messages. Currently comes packaged with these gateway providers:

- [Twilio](https://www.twilio.com) (supports: sending, receiving, delivery reports)
- [Nexmo](https://www.nexmo.com) (supports: sending, receiving, delivery reports)
- [Cellsynt](https://www.cellsynt.com) (supports: sending, premium, receiving, delivery reports)
- [Telenor SMS Pro](https://www.smspro.se/) (supports: sending, delivery reports)

You can add and use your own gateway provider. This library enables easy switching between different gateways.


## Installation
Add the package as a requirement to your `composer.json`:
```bash
$ composer require jandreasn/an-sms php-http/guzzle6-adapter php-http/message
```

Why `php-http/guzzle6-adapter php-http/message`? This package requires a php-http client implementation. You can 
install this one, an adapter for a client you are already using or another one. There are several
 [adapters](https://packagist.org/providers/php-http/client-implementation) available. Read more about this in the 
 [HTTPlug docs](http://docs.php-http.org/en/latest/httplug/users.html).

If you want to use the **Twilio** gateway provider you also need to install the Twilio SDK:

```bash
$ composer require twilio/sdk
```

If you want to use the **Nexmo** gateway provider you also need to install the Nexmo client:

```bash
$ composer require nexmo/client
```

## Usage
```php
<?php

use AnSms\{
    SmsTransceiver,
    Message\Message,
    Message\PremiumMessage,
    Gateway\Provider\CellsyntGateway
};

$gateway = new CellsyntGateway('username', 'password');
$smsTransceiver = new SmsTransceiver($gateway);

// Send SMS
$message = Message::create('46700123456', 'Hello world!');
$smsTransceiver->sendMessage($message);

// Receive SMS
$receivedMessage = $smsTransceiver->receiveMessage($_GET);

// Receive SMS delivery report
$deliveryReport = $smsTransceiver->receiveDeliveryReport($_GET);

// Send Premium SMS
$premiumMessage = PremiumMessage::createFromIncomingMessage(
    'Thanks for your payment!', 
    5, 
    $receivedMessage
);
$smsTransceiver->sendMessage($premiumMessage);

```


## Requirements
- Requires PHP 7.1 or above.

## Author
Andreas Nilsson (<http://github.com/jandreasn>)

## License
This software is licensed under the MIT license - see the [LICENSE](LICENSE.md) file for details.
