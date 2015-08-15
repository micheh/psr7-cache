# Cache helpers for PSR-7 HTTP Messages

[![Build Status](https://secure.travis-ci.org/micheh/psr7-cache.svg?branch=master)](https://secure.travis-ci.org/micheh/psr7-cache)

This library provides an easy way to add cache relevant headers to a PSR-7 HTTP message implementation.


## Installation

Install this library using composer:

```console
$ composer require micheh/psr7-cache
```

## Quickstart

To enable caching of HTTP responses, create an instance of `CacheUtil`, call the method `withCache` and provide your PSR-7 response.

```php
/** @var \Psr\Http\Message\ResponseInterface $response */

$util = new \Micheh\Cache\CacheUtil();
$response = $util->withCache($response);
```

This will add the header `Cache-Control: private, max-age=600` to your response.
With this header the response will only be cached by the person who sent the request and will be cached for 600 seconds (10 min).

After the specified 10 minutes the cache is expired. The client will make a new request to the application and get the newest version.
You should also add an `ETag` header (and `Last-Modified` header if you know when the resource was last modified) so that the application does not have to send the response again in case the client already has the current version (Cache Validation).

```php
/** @var \Psr\Http\Message\ResponseInterface $response */

$util = new \Micheh\Cache\CacheUtil();
$response = $util->withCache($response);
$response = $util->withETag($response, 'my-etag');
$response = $util->withLastModified($response, '2015-08-16 16:31:12');
```


## References

- [RFC7234: Caching](https://tools.ietf.org/html/rfc7234)
- [RFC7232: Conditional Requests](https://tools.ietf.org/html/rfc7232) (Cache Validation)
- [RFC5861: Cache-Control Extensions for Stale Content](https://tools.ietf.org/html/rfc5861)


## License

The files in this archive are licensed under the BSD-3-Clause license.
You can find a copy of this license in [LICENSE.md](LICENSE.md).
