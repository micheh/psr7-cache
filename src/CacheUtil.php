<?php

namespace Micheh\Cache;

use DateTime;
use DateTimeZone;
use Exception;
use InvalidArgumentException;
use Micheh\Cache\Header\CacheControl;
use Micheh\Cache\Header\ResponseCacheControl;
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
     * Method to add a Cache-Control header to a PSR-7 response, which should enable caching.
     * Set the `private` parameter to false to enable shared caches to cache the response (for
     * example when the response is usable by everyone and does not contain individual information).
     * With the `$maxAge` parameter you can specify how many seconds a response should be cached.
     *
     * By default this method specifies a `private` cache, which caches for 10 minutes. For more
     * options use the `withCacheControl` method.
     *
     * @see withCacheControl
     *
     * @param ResponseInterface $response PSR-7 response to add the header to
     * @param bool $public True for public, false for private
     * @param int $maxAge How many seconds the response should be cached. Default: 600 (10 min)
     * @return ResponseInterface
     * @throws InvalidArgumentException If the type is invalid
     */
    public function withCache(ResponseInterface $response, $public = false, $maxAge = 600)
    {
        $control = new ResponseCacheControl();
        $control = $public ? $control->withPublic() : $control->withPrivate();
        $control = $control->withMaxAge($maxAge);

        return $this->withCacheControl($response, $control);
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
        $control = new ResponseCacheControl();
        $control = $control->withCachePrevention();

        return $this->withCacheControl($response, $control);
    }

    /**
     * Method to add an Expires header to a PSR-7 response. Use this header if you have a specific
     * time when the representation will expire, otherwise use the more fine-tuned `Cache-Control`
     * header with the `max-age` directive. If you need to support the old HTTP/1.0 protocol and
     * want to set a relative expiration, use the `withRelativeExpires` method.
     *
     * @see withCache
     * @see withCacheControl
     * @see withRelativeExpires
     * @link https://tools.ietf.org/html/rfc7234#section-5.3
     *
     * @param ResponseInterface $response PSR-7 response to add the header to
     * @param int|string|DateTime $time UNIX timestamp, date string or DateTime object
     * @return ResponseInterface
     * @throws InvalidArgumentException If the time could not be parsed
     */
    public function withExpires(ResponseInterface $response, $time)
    {
        return $response->withHeader('Expires', $this->getTimeFromValue($time));
    }

    /**
     * Method to add a relative `Expires` header to a PSR-7 response. Use this header if want to
     * support the old HTTP/1.0 protocol and have a relative expiration time. Otherwise use the
     * `Cache-Control` header with the `max-age` directive.
     *
     * @see withCache
     * @see withCacheControl
     * @see withExpires
     * @link https://tools.ietf.org/html/rfc7234#section-5.3
     *
     * @param ResponseInterface $response PSR-7 response to add the header to
     * @param int $seconds Number of seconds the response should be cached
     * @return ResponseInterface
     * @throws InvalidArgumentException If the seconds parameter is not an integer
     */
    public function withRelativeExpires(ResponseInterface $response, $seconds)
    {
        if (!is_int($seconds)) {
            throw new InvalidArgumentException(
                'Expected an integer with the number of seconds, received ' . gettype($seconds) . '.'
            );
        }

        return $response->withHeader('Expires', $this->getTimeFromValue(time() + $seconds));
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
     * @return ResponseInterface
     * @throws InvalidArgumentException if the ETag value is not valid
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
     * The provided time can be an UNIX timestamp, a parseable string or a DateTime instance.
     *
     * @see withETag
     * @see getTimeFromValue
     * @link https://tools.ietf.org/html/rfc7232#section-2.2
     *
     * @param ResponseInterface $response PSR-7 response to add the header to
     * @param int|string|DateTime $time UNIX timestamp, date string or DateTime object
     * @return ResponseInterface
     * @throws InvalidArgumentException If the time could not be parsed
     */
    public function withLastModified(ResponseInterface $response, $time)
    {
        return $response->withHeader('Last-Modified', $this->getTimeFromValue($time));
    }

    /**
     * Method to add a Cache-Control header to the provided PSR-7 message.
     *
     * @link https://tools.ietf.org/html/rfc7234#section-5.2
     *
     * @param MessageInterface $message PSR-7 message to add the Cache-Control header to
     * @param CacheControl $cacheControl Cache-Control object to add to the message
     * @return MessageInterface The PSR-7 message with the added Cache-Control header
     * @throws InvalidArgumentException If the Cache-Control header is invalid
     */
    public function withCacheControl(MessageInterface $message, CacheControl $cacheControl)
    {
        return $message->withHeader('Cache-Control', (string) $cacheControl);
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
            return $noneMatch === '*' || in_array($eTag, preg_split('/\s*,\s*/', $noneMatch), true);
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

        if (!$response->hasHeader('Cache-Control')) {
            return true;
        }

        $cacheControl = $this->getCacheControl($response);
        return !$cacheControl->hasNoStore() && !$cacheControl->isPrivate();
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
        if ($response->hasHeader('Cache-Control')) {
            $lifetime = $this->getCacheControl($response)->getLifetime();

            if ($lifetime !== null) {
                return $lifetime;
            }
        }

        $expires = $response->getHeaderLine('Expires');
        if ($expires) {
            $now = $response->getHeaderLine('Date');
            $now = $now ? strtotime($now) : time();
            return max(0, strtotime($expires) - $now);
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
     * parameter can be an UNIX timestamp, a parseable string or a DateTime object.
     *
     * @link https://secure.php.net/manual/en/datetime.formats.php
     *
     * @param int|string|DateTime $time Timestamp, date string or DateTime object
     * @return string Formatted timestamp
     * @throws InvalidArgumentException If the time could not be parsed
     */
    protected function getTimeFromValue($time)
    {
        $format = 'D, d M Y H:i:s \G\M\T';

        if (is_int($time)) {
            return gmdate($format, $time);
        }

        if (is_string($time)) {
            try {
                $time = new DateTime($time);
            } catch (Exception $exception) {
                // if it is an invalid date string an exception is thrown below
            }
        }

        if ($time instanceof DateTime) {
            $time = clone $time;
            $time->setTimezone(new DateTimeZone('UTC'));
            return $time->format($format);
        }

        throw new InvalidArgumentException('Could not create a valid date from ' . gettype($time) . '.');
    }

    /**
     * Parses the Cache-Control header of a response and returns the Cache-Control object.
     *
     * @param ResponseInterface $response
     * @return ResponseCacheControl
     */
    protected function getCacheControl(ResponseInterface $response)
    {
        return ResponseCacheControl::fromString($response->getHeaderLine('Cache-Control'));
    }
}
