# PHP LetsEncrypt Wildcard DNS Client for Cloudflare DNS

This is a Cloudflare DNS implementation for `ssigwart/letsencryptdns`.

## Install
```sh
composer install ssigwart/letsencryptdns-cloudflare
```

## Usage
```php
$cfDnsProvider = new CloudflareDNSProvider('api_token', 'zone');
```
