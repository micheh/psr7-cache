# Cache helpers for PSR-7 HTTP Messages

[![Build Status](https://secure.travis-ci.org/micheh/psr7-cache.svg?branch=master)](https://secure.travis-ci.org/micheh/psr7-cache)
[![codecov.io](http://codecov.io/github/micheh/psr7-cache/coverage.svg?branch=master)](http://codecov.io/github/micheh/psr7-cache?branch=master)

This library provides an easy way to either add cache relevant headers to a PSR-7 HTTP message implementation, or to extract cache information from a PSR-7 message (e.g. if a response is cacheable).


## Installation

Install this library using [Composer](https://getcomposer.org/):

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
With this header the response will only be cached by the client who sent the request and will be cached for 600 seconds (10 min).
During this time the client should use the response from the cache and should not make a new request to the application.

### Cache Validators
The application should also add Cache Validators to the response: An `ETag` header (and `Last-Modified` header if you know when the resource was last modified).
This way the client will also include the `ETag` and `Last-Modified` information in the request and the application can check if the client still has the current version.

```php
/** @var \Psr\Http\Message\ResponseInterface $response */

$util = new \Micheh\Cache\CacheUtil();
$response = $util->withCache($response);
$response = $util->withETag($response, 'my-etag');
$response = $util->withLastModified($response, '2015-08-16 16:31:12');
```

### Revalidate a response
To determine if the client still has a current copy of the page and the response is not modified, you can use the `isNotModified` method.
Add the cache headers to the response and then call the method with both the request and the response.
If the response is not modified, return the empty response with the cache headers and a status code `304`.
This will instruct the client to use the cached copy from the previous request, saving you CPU/memory usage and bandwidth.
Therefore it is important to keep the code before the `isNotModified` call as lightweight as possible to increase performance.
Don't create the complete response before this method.

```php
/** @var \Psr\Http\Message\RequestInterface $request */
/** @var \Psr\Http\Message\ResponseInterface $response */

$util = new \Micheh\Cache\CacheUtil();
$response = $util->withCache($response);
$response = $util->withETag($response, 'my-etag');
$response = $util->withLastModified($response, '2015-08-16 16:31:12');

if ($util->isNotModified($request, $response)) {
    return $response->withStatus(304);
}

// create the body of the response
```


## Available helper methods

Method                | Description (see the phpDoc for more information)
--------------------- | ------------------------------------------------------------------------
`withCache`           | Convenience method to add a `Cache-Control` header, which allows caching
`withCachePrevention` | Convenience method to prevent caching
`withExpires`         | Adds an `Expires` header (date can be absolute or relative)
`withETag`            | Adds an `ETag` header
`withLastModified`    | Adds a `Last-Modified` header (date can be absolute or relative)
`withCacheControl`    | Adds a `Cache-Control` header with the provided directives (from array)
`isNotModified`       | Checks if a response is not modified
`isCacheable`         | Checks if a response is cacheable by a public cache
`isFresh`             | Checks if a response is fresh (age smaller than lifetime)
`getLifetime`         | Returns the lifetime of a response (how long it should be cached)
`getAge`              | Returns the age of a response (how long it was in the cache)


## References

- [RFC7234: Caching](https://tools.ietf.org/html/rfc7234)
- [RFC7232: Conditional Requests](https://tools.ietf.org/html/rfc7232) (Cache Validation)
- [RFC5861: Cache-Control Extensions for Stale Content](https://tools.ietf.org/html/rfc5861)


## License

The files in this archive are licensed under the BSD-3-Clause license.
You can find a copy of this license in [LICENSE.md](LICENSE.md).
