# Cache helpers for PSR-7 HTTP Messages

[![Build Status](https://secure.travis-ci.org/micheh/psr7-cache.svg?branch=master)](https://secure.travis-ci.org/micheh/psr7-cache)
[![Code Coverage](https://scrutinizer-ci.com/g/micheh/psr7-cache/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/micheh/psr7-cache/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/micheh/psr7-cache/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/micheh/psr7-cache/?branch=master)

This library provides an easy way to either add cache relevant headers to a PSR-7 HTTP message implementation, or to extract cache and conditional request information from a PSR-7 message (e.g. if a response is cacheable).
It also provides a `Cache-Control` value object to provide an object oriented interface for the manipulation of the `Cache-Control` header.


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
This way the client will also include the `ETag` and `Last-Modified` information in the request and the application can check if the client still has the current state.

```php
/** @var \Psr\Http\Message\ResponseInterface $response */

$util = new \Micheh\Cache\CacheUtil();
$response = $util->withCache($response);
$response = $util->withETag($response, 'my-etag');
$response = $util->withLastModified($response, new \DateTime());
```

### Revalidate a response
To determine if the client still has a current copy of the page and the response is not modified, you can use the `isNotModified` method.
Simply add the cache headers to the response and then call the method with both the request and the response.
The method will automatically compare the `If-None-Match` header of the request with the `ETag` header of the response (and/or the `If-Modified-Since` header of the request with the `Last-Modified` header of the response if available).
If the response is not modified, return the empty response with the cache headers and a status code `304` (Not Modified).
This will instruct the client to use the cached copy from the previous request, saving you CPU/memory usage and bandwidth.
Therefore it is important to keep the code before the `isNotModified` call as lightweight as possible to increase performance.
Don't create the complete response before this method.

```php
/** @var \Psr\Http\Message\RequestInterface $request */
/** @var \Psr\Http\Message\ResponseInterface $response */

$util = new \Micheh\Cache\CacheUtil();
$response = $util->withCache($response);
$response = $util->withETag($response, 'my-etag');
$response = $util->withLastModified($response, new \DateTime());

if ($util->isNotModified($request, $response)) {
    return $response->withStatus(304);
}

// create the body of the response
```


### Conditional request with unsafe method
While the procedure described above is usually optional and for safe methods (GET and HEAD), it is also possible to enforce that the client has the current resource state.
This is useful for unsafe methods (e.g. POST, PUT, PATCH or DELETE), because it can prevent lost updates (e.g. if another client updates the resource before your request).
It is a good idea to initially check if the request includes the appropriate headers (`If-Match` for an `ETag` and/or `If-Unmodified-Since` for a `Last-Modified` date) with the `hasStateValidator` method.
If the request does not include this information, abort the execution and return status code `428` (Precondition Required) or status code `403` (Forbidden) if you only want to use the original status codes.

```php
/** @var \Psr\Http\Message\RequestInterface $request */

$util = new \Micheh\Cache\CacheUtil();
if (!$util->hasStateValidator($request)) {
    return $response->withStatus(428);
}
```

If the state validators are included in the request, you can check if the request has the current resource state and not an outdated version with the method `hasCurrentState`.
If the request has an outdated resource state (another `ETag` or an older `Last-Modified` date), abort the execution and return status code `412` (Precondition Failed).
Otherwise you can continue to process the request and update/delete the resource.
Once the resource is updated, it is a good idea to include the updated `ETag` (and `Last-Modified` date if available) in the response.

```php
/** @var \Psr\Http\Message\RequestInterface $request */
/** @var \Psr\Http\Message\ResponseInterface $response */

$util = new \Micheh\Cache\CacheUtil();
if (!$util->hasStateValidator($request)) {
    return $response->withStatus(428);
}

$eTag = 'my-etag'
$lastModified = new \DateTime();
if (!$util->hasCurrentState($request, $eTag, $lastModified)) {
    return $response->withStatus(412);
}

// process the request
```


## Available helper methods

Method                | Description (see the phpDoc for more information)
--------------------- | ------------------------------------------------------------------------
`withCache`           | Convenience method to add a `Cache-Control` header, which allows caching
`withCachePrevention` | Convenience method to prevent caching
`withExpires`         | Adds an `Expires` header from a timestamp, string or DateTime
`withRelativeExpires` | Adds an `Expires` header with a specific lifetime
`withETag`            | Adds an `ETag` header
`withLastModified`    | Adds a `Last-Modified` header from a timestamp, string or DateTime
`withCacheControl`    | Adds a `Cache-Control` header from a string or value object
`hasStateValidator`   | Checks if it is possible to determine the resource state
`hasCurrentState`     | Checks if a request has the current resource state
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
