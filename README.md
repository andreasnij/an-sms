# AnSms - A PHP SMS library

[![Version](http://img.shields.io/packagist/v/jandreasn/an-sms.svg?style=flat-square)](https://packagist.org/packages/jandreasn/an-sms)

An extendable library for sending and receiving SMS messages. Currently comes packaged with these gateways:

<br>

|                                                   | Send SMS | Delivery reports | Receive SMS | Premium SMS |
|---------------------------------------------------|:--------:|:----------------:|:-----------:|:-----------:|
| [46elks](https://46elks.com/)                     |    ✔     |       ✔          |      ✔      |             |
| [Cellsynt](https://www.cellsynt.com)              |    ✔     |       ✔          |      ✔      |      ✔      |
| [Vonage (formerly Nexmo)](https://www.vonage.com) |    ✔     |       ✔          |      ✔      |             |
| [Twilio](https://www.twilio.com)                  |    ✔     |       ✔          |      ✔      |             |
| [Telenor SMS Pro](https://www.smspro.se/)         |    ✔     |       ✔          |             |             |


You can add and use your own gateway. This library enables easy switching between different gateways.


## Installation
Add the package as a requirement to your `composer.json`:
```bash
$ composer require jandreasn/an-sms guzzlehttp/guzzle:^7.0 guzzlehttp/psr7:^2.0
```

The `guzzlehttp/guzzle:^7.0 guzzlehttp/psr7:^2.0` part is optional depending on your environment. This package
requires implementations of **PSR-7**: HTTP message interfaces, **PSR-17**: HTTP Factories and
**PSR-18**: HTTP Client, which Guzzle provides. You may choose to use any other provider implementing these interfaces.
The package is not dependant on Guzzle, just the PSR interfaces.

If you want to use the **Twilio** gateway you also need to install the Twilio SDK:

```bash
$ composer require twilio/sdk
```

If you want to use the **Vonage** gateway you also need to install the Vonage client:

```bash
$ composer require vonage/client-core
```

## Usage
```php
<?php

use AnSms\{
    SmsTransceiver,
    Message\Message,
    Message\PremiumMessage,
    Gateway\CellsyntGateway
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
- Requires PHP 8.0 or above.

## Upgrading

Please see [UPGRADING](UPGRADING.md) for details.

## Author
Andreas Nilsson (<https://github.com/andreasnij>)

## License
This software is licensed under the MIT license - see the [LICENSE](LICENSE.md) file for details.
