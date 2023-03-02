# Upgrading guide

## 0.6 to 1.0
- The minimum required PHP version is now 8.1. If you are still on PHP 8.0, you can use version
  AnSms 0.6 until you upgrade.


## 0.5 to 0.6
- If you previously installed the `php-http/guzzle6-adapter` and
  `php-http/message` packages when installing this package, remove
  them and follow the new install instructions in the [README](README.md).
- If you previously installed the `nexmo/client` package when installing
  this package, you now need to replace it with `vonage/client-core`.
- Remove the `Provider` namespace part from gateway classes fully qualified
  name. They're located directly in `AnSms\Gateway` now.
