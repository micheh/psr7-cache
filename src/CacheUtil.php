<?php

namespace Micheh\Cache;

use DateTime;
use DateTimeZone;
use Exception;
use InvalidArgumentException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Util class to add cache headers to PSR-7 HTTP messages and to parse PSR-7 cache headers.
 *
 * @author Michel Hunziker <php@michelhunziker.com>
 * @copyright Copyright (c) 2015, Michel Hunziker <php@michelhunziker.com>
 * @license http://www.opensource.org/licenses/BSD-3-Clause The BSD-3-Clause License
 */
class CacheUtil
{
    /**
     * @var array Allowed directives for Cache-Control
     */
    private static $allowedDirectives = [
        'token' => [
            'max-age' => true,
            's-maxage' => true,
            'max-stale' => true,
            'min-fresh' => true,
            'stale-while-revalidate' => true,
            'stale-if-error' => true,
        ],
        'flag' => [
            'must-revalidate' => true,
            'proxy-revalidate' => true,
            'no-cache' => true,
            'no-store' => true,
            'no-transform' => true,
            'only-if-cached' => true,
        ],
    ];

    /**
     * Method to add a Cache-Control header to a PSR-7 response, which should enable caching.
     * Use the type `public` to enable shared caches to cache the response or use `private`
     * otherwise. With the `$maxAge` parameter you can specify how many seconds a response should
     * be cached.
     *
     * By default this method specifies a `private` cache, which caches for 10 minutes. For more
     * options use the `withCacheControl` method.
     *
     * @see withCacheControl
     *
     * @param ResponseInterface $response PSR-7 response to add the header to
     * @param string $type public or private. Default: private
     * @param int $maxAge How many seconds the response should be cached. Default: 600 (10 min)
     * @return ResponseInterface
     * @throws InvalidArgumentException If the type is invalid
     */
    public function withCache(ResponseInterface $response, $type = 'private', $maxAge = 600)
    {
        return $this->withCacheControl($response, ['type' => $type, 'max-age' => $maxAge]);
    }

    /**
     * Method to add a Cache-Control header to a PSR-7 response, which should prevent caching.
     * Adds `no-cache, no-store, must-revalidate`. Use the `withCacheControl` method for more
     * options.
     *
     * @see withCacheControl
     *
     * @param ResponseInterface $response PSR-7 response to prevent caching
     * @return ResponseInterface
     */
    public function withCachePrevention(ResponseInterface $response)
    {
        return $this->withCacheControl(
            $response,
            ['no-cache' => true, 'no-store' => true, 'must-revalidate' => true]
        );
    }

    /**
     * Method to add an Expires header to a PSR-7 response. Use this header if you have a specific
     * time when the representation will expire, otherwise use the more fine-tuned Cache-Control
     * header.
     *
     * @see withCache
     * @see withCacheControl
     * @see getTimeFromValue
     * @link https://tools.ietf.org/html/rfc7234#section-5.3
     *
     * @param ResponseInterface $response PSR-7 response to add the header to
     * @param int|string|DateTime $time Time when the representation is expired
     * @param bool $relative If the provided time is relative
     * @return ResponseInterface
     * @throws InvalidArgumentException If the time could not be parsed
     */
    public function withExpires(ResponseInterface $response, $time, $relative = false)
    {
        return $response->withHeader('Expires', $this->getTimeFromValue($time, $relative));
    }

    /**
     * Method to add an ETag header to a PSR-7 response. If possible, always add an ETag to a
     * response you want to cache. If the last modified time is easily accessible, use both the
     * ETag and Last-Modified header. Use a weak ETag if you want to compare if a resource is only
     * semantically the same.
     *
     * @see withLastModified
     * @link https://tools.ietf.org/html/rfc7232#section-2.3
     *
     * @param ResponseInterface $response PSR-7 response to add the header to
     * @param string $eTag ETag to add
     * @param bool $weak If the provided ETag is weak
     * @return MessageInterface
     */
    public function withETag(ResponseInterface $response, $eTag, $weak = false)
    {
        $eTag = '"' . trim($eTag, '"') . '"';
        if ($weak) {
            $eTag = 'W/' . $eTag;
        }

        return $response->withHeader('ETag', $eTag);
    }

    /**
     * Method to add a Last-Modified header to a PSR-7 response. Add a Last-Modified header if you
     * have an easy access to the last modified time, otherwise use only an ETag.
     *
     * The provided time can be an UNIX timestamp, a parseable string or a DateTime instance. If the
     * $relative parameter is `true` and the time parameter is an integer, the seconds from the time
     * parameter will be added to the current time.
     *
     * @see withETag
     * @see getTimeFromValue
     * @link https://tools.ietf.org/html/rfc7232#section-2.2
     *
     * @param ResponseInterface $response PSR-7 response to add the header to
     * @param int|string|DateTime $time Last modified time
     * @param bool $relative If the provided time is relative
     * @return ResponseInterface
     * @throws InvalidArgumentException If the time could not be parsed
     */
    public function withLastModified(ResponseInterface $response, $time, $relative = false)
    {
        return $response->withHeader('Last-Modified', $this->getTimeFromValue($time, $relative));
    }

    /**
     * Method to add a Cache-Control header to a provided PSR-7 message. The directives array
     * should be an associative array with the directive as key. For directives which are flags
     * (e.g. `must-revalidate`) use `true` to include the directive or `false` to exclude it.
     *
     * Available directives:
     * - `type`: public or private. Use public to enable shared caches (Response only).
     * - `max-age`: How many seconds to cache (Request & Response).
     * - `s-maxage`: How many seconds shared caches should cache (Response only).
     * - `max-stale`: How many seconds a stale representation is acceptable (Request only).
     * - `min-fresh`: How many seconds a representation should still be fresh (Request only).
     * - `stale-while-revalidate`: How many seconds a stale representation can be used while
     *   revalidating in the background (Response only).
     * - `stale-if-error`: How many seconds a stale representation can be used in the case of an
     *   error (Response only).
     * - `must-revalidate`: Whether a stale representation should be validated (Response only).
     * - `proxy-revalidate`: Whether a public cache should validate a stale representation (Response only).
     * - `no-cache`: Whether a representation should be cached (Request & Response).
     * - `no-store`: Whether a representation should be stored (Request & Response).
     * - `no-transform`: Whether the payload can be transformed (Request & Response).
     * - `only-if-cached`: Whether only a stored response should be returned (Request only).
     *
     * @link https://tools.ietf.org/html/rfc7234#section-5.2
     *
     * @param MessageInterface $message PSR-7 message to add the Cache-Control header to
     * @param array $directives Array with the Cache-Control directives to add
     * @return MessageInterface The PSR-7 message with the added Cache-Control header
     * @throws InvalidArgumentException If the type is invalid or an unknown directive is used
     */
    public function withCacheControl(MessageInterface $message, array $directives)
    {
        $headerParts = [];

        foreach ($directives as $directive => $value) {
            if ($directive === 'type') {
                if ($value !== 'public' && $value !== 'private') {
                    throw new InvalidArgumentException(sprintf(
                        'Invalid cache control type "%s", valid values are "public" and "private".',
                        $value
                    ));
                }
            } elseif (isset(self::$allowedDirectives['token'][$directive])) {
                $value = $directive . '=' . (int) $value;
            } elseif (isset(self::$allowedDirectives['flag'][$directive])) {
                if (!$value) {
                    continue;
                }

                $value = $directive;
            } else {
                throw new InvalidArgumentException('Unknown cache control directive: ' . $directive);
            }

            $headerParts[] = $value;
        }

        return $message->withHeader('Cache-Control', implode(', ', $headerParts));
    }

    /**
     * Method to check if the response is not modified and the request still has a valid cache, by
     * comparing the `ETag` headers. If no `ETag` is available and the method is GET or HEAD, the
     * `Last-Modified` header is used for comparison.
     *
     * Returns `true` if the request is not modified and the cache is still valid. In this case the
     * application should return an empty response with the status code `304`. If the returned
     * value is `false`, the client either has no cached representation or has an outdated cache.
     *
     * @link https://tools.ietf.org/html/rfc7232#section-6
     *
     * @param RequestInterface $request Request to check against
     * @param ResponseInterface $response Response with ETag and/or Last-Modified header
     * @return bool True if not modified, false if invalid cache
     */
    public function isNotModified(RequestInterface $request, ResponseInterface $response)
    {
        $eTag = $response->getHeaderLine('ETag');
        $noneMatch = $request->getHeaderLine('If-None-Match');
        if ($eTag && $noneMatch) {
            return $noneMatch === '*' || in_array($eTag, explode(', ', $noneMatch), true);
        }

        if (!in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
            return false;
        }

        $lastModified = $response->getHeaderLine('Last-Modified');
        $modifiedSince = $request->getHeaderLine('If-Modified-Since');

        return ($lastModified && $modifiedSince && strtotime($modifiedSince) >= strtotime($lastModified));
    }

    /**
     * Method to check if a response can be cached by a shared cache. The method will check if the
     * response status is cacheable and if the Cache-Control header explicitly disallows caching.
     * The method does NOT check if the response is fresh. If you want to store the response on the
     * application side, check both `isCacheable` and `isFresh`.
     *
     * @see isFresh
     * @link https://tools.ietf.org/html/rfc7234#section-3
     *
     * @param ResponseInterface $response Response to check if it is cacheable
     * @return bool True if the response is cacheable by a shared cache and false otherwise
     */
    public function isCacheable(ResponseInterface $response)
    {
        if (!in_array($response->getStatusCode(), [200, 203, 300, 301, 302, 404, 405, 410], true)) {
            return false;
        }

        $cacheControl = $response->getHeaderLine('Cache-Control');
        if (!$cacheControl) {
            return true;
        }

        return strpos($cacheControl, 'no-store') === false && strpos($cacheControl, 'private') === false;
    }

    /**
     * Method to check if a response is still fresh. This is useful if you want to know if can still
     * serve a saved response or if you have to create a new response. The method returns true if the
     * lifetime of the response is available and is greater than the age of the response. If the
     * method returns false, the response is outdated and should be renewed. In case no lifetime
     * information is available (no Cache-Control and Expires header), the method returns `null`.
     *
     * @see getLifetime
     * @see getAge
     * @link https://tools.ietf.org/html/rfc7234#section-4.2
     *
     * @param ResponseInterface $response Response to check
     * @return bool|null True if the response is still fresh and false otherwise. Null if unknown
     */
    public function isFresh(ResponseInterface $response)
    {
        $lifetime = $this->getLifetime($response);
        if ($lifetime !== null) {
            return $lifetime > $this->getAge($response);
        }

        return null;
    }

    /**
     * Returns the lifetime of the provided response (how long the response should be cached once it
     * is created). The method will lookup the `s-maxage` Cache-Control directive first and fallback
     * to the `max-age` directive. If both Cache-Control directives are not available, the `Expires`
     * header is used to compute the lifetime. Returns the lifetime in seconds if available and
     * `null` if the lifetime cannot be calculated.
     *
     * @param ResponseInterface $response Response to get the lifetime from
     * @return int|null Lifetime in seconds or null if not available
     */
    public function getLifetime(ResponseInterface $response)
    {
        $cacheControl = $response->getHeaderLine('Cache-Control');
        if ($cacheControl) {
            $lifetime = $this->getTokenValue($cacheControl, 's-maxage');
            if ($lifetime !== null) {
                return (int) $lifetime;
            }

            $lifetime = $this->getTokenValue($cacheControl, 'max-age');
            if ($lifetime !== null) {
                return (int) $lifetime;
            }
        }

        $expires = $response->getHeaderLine('Expires');
        if ($expires) {
            $now = $response->getHeaderLine('Date');
            $now = $now ? strtotime($now) : time();
            return strtotime($expires) - $now;
        }

        return null;
    }

    /**
     * Returns the age of the response (how long the response is already cached). Uses the `Age`
     * header to determine the age. If the header is not available, the `Date` header is used
     * instead. The method will return the age in seconds or `null` if the age cannot be calculated.
     *
     * @param ResponseInterface $response Response to get the age from
     * @return int|null Age in seconds or null if not available
     */
    public function getAge(ResponseInterface $response)
    {
        $age = $response->getHeaderLine('Age');
        if ($age !== '') {
            return (int) $age;
        }

        $date = $response->getHeaderLine('Date');
        if ($date) {
            return max(0, time() - strtotime($date));
        }

        return null;
    }

    /**
     * Returns a formatted timestamp of the time parameter, to use in the HTTP headers. The time
     * parameter can be an UNIX timestamp, a parseable string or a DateTime object. If the relative
     * parameter is true and the time parameter is an integer, the time parameter will be added to
     * the current time.
     *
     * @param int|string|DateTime $time Time object to create
     * @param bool $relative Whether the provided time is relative to the current time
     * @return string Formatted timestamp
     * @throws InvalidArgumentException If the time could not be parsed
     */
    protected function getTimeFromValue($time, $relative = false)
    {
        $format = 'D, d M Y H:i:s';
        $value = null;

        if (is_string($time)) {
            try {
                $time = new DateTime($time, new DateTimeZone('UTC'));
            } catch (Exception $exception) {
                // if it is an invalid date string an exception is thrown below
            }
        }

        if (is_int($time)) {
            if ($relative) {
                $time = time() + $time;
            }

            $value = gmdate($format, $time);
        } elseif ($time instanceof DateTime) {
            $time = clone $time;
            $time->setTimezone(new DateTimeZone('UTC'));
            $value = $time->format($format);
        }

        if (!$value) {
            throw new InvalidArgumentException('Could not create a valid date from ' . gettype($time) . '.');
        }

        return $value . ' GMT';
    }

    /**
     * Returns the value of a directive in the provided header. Example: For the header `max-age=30`
     * the value for the token `max-age` will be `30`.
     *
     * @param string $header Header to search the directive in
     * @param string $token Directive to fetch
     * @return string|null
     */
    protected function getTokenValue($header, $token)
    {
        $index = strpos($header, $token);
        if ($index !== false) {
            $index = $index + strlen($token) + 1;
            $commaIndex = strpos($header, ',', $index);
            return $commaIndex ? substr($header, $index, $commaIndex - $index) : substr($header, $index);
        }

        return null;
    }
}
