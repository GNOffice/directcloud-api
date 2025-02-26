# A minimal implementation of DirectCloud API

[![Latest Version on Packagist](https://img.shields.io/packagist/v/gn-office/directcloud-api.svg?style=flat-square)](https://packagist.org/packages/spatie/dropbox-api)
[![Total Downloads](https://img.shields.io/packagist/dt/gn-office/directcloud-api.svg?style=flat-square)](https://packagist.org/packages/spatie/dropbox-api)


This is a minimal PHP implementation of the [DirectCloud API](https://directcloud.jp/api_reference/). It contains only the methods needed for [our flysystem-directcloud adapter](https://github.com/gn-office/flysystem-directcloud). We are open however to PRs that add extra methods to the client.

## Installation

You can install the package via composer:

``` bash
composer require gn-office/directcloud-api
```

## Usage
The first thing you need to do is to get an authorization information at DirectCloud. You'll find more info at [DirectCloud API Documentation](https://directcloud.jp/api_reference/detail/%E3%83%A6%E3%83%BC%E3%82%B6%E3%83%BC/Auth).

```php
use GNOffice\DirectCloud\Client;

$client = new Client([$service, $service_key, $code, $id, $password]);
// or
$client = new Client([$service, $service_key, $access_key]);
// or
$client = new Client($access_token);

//create a folder
$client->createFolder($node, $name);

//list a folder
$client->getList($node);
```

## Endpoints

Look in [the source code of `GNOffice\DirectCloud\Client`](https://github.com/gn-office/directcloud-api/blob/master/src/Client.php) to discover the methods you can use.

If you do not find your favorite method, you can directly use the `v1Request` and `v2Request` functions.

```php
public function v1Request(string $method, string $endpoint, string $requestOption = null, $parameters = [])

public function v2Request(string $method, string $endpoint, string $requestOption, $parameters = [], $extraHeaders = [])
```

Here's an example:

```php
$client->v1Request('/openapp/v1/files/search/'.{$node}, ['keyword' => 'something', 'sort' => '+datetime', 'limit' => 100]);
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
