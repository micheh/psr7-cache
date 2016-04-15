<?php
/**
 * PSR-7 Cache Helpers
 *
 * @copyright Copyright (c) 2016, Michel Hunziker <php@michelhunziker.com>
 * @license http://www.opensource.org/licenses/BSD-3-Clause The BSD-3-Clause License
 */

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
     * Checks if a request has included validators (`ETag` and/or `Last-Modified` Date) which allow
     * to determine if the client has the current resource state. The method will check if the
     * `If-Match` and/or `If-Unmodified-Since` header is present.
     *
     * This method can be used for unsafe conditional requests (neither GET nor HEAD). If the
     * request did not include a state validator (method returns `false`), abort the execution and
     * return a `428` http status code (or `403` if you only want to use the original status
     * codes). If the requests includes state validators (method returns `true`), you can continue
     * and check if the client has the current state with the `hasCurrentState` method.
     *
     * @see hasCurrentState
     * @link https://tools.ietf.org/html/rfc7232#section-6
     *
     * @param RequestInterface $request PSR-7 request to check
     * @return bool True if the request includes state validators, false if it has no validators
     */
    public function hasStateValidator(RequestInterface $request)
    {
        return $request->hasHeader('If-Match') || $request->hasHeader('If-Unmodified-Since');
    }

    /**
     * Checks if the provided PSR-7 request has the current resource state. The method will check
     * the `If-Match` and `If-Modified-Since` headers with the current ETag (and/or the Last-Modified
     * date if provided). In addition, for a request which is not GET or HEAD, the method will check
     * the `If-None-Match` header.
     *
     * Use this method to check conditional unsafe requests and to prevent lost updates. If the
     * request does not have the current resource state (method returns `false`), abort and return
     * status code `412`. In contrast, if the client has the current version of the resource (method
     * returns `true`) you can safely continue the execution and update/delete the resource.
     *
     * @link https://tools.ietf.org/html/rfc7232#section-6
     *
     * @param RequestInterface $request PSR-7 request to check
     * @param string $eTag Current ETag of the resource
     * @param null|int|string|DateTime $lastModified Current Last-Modified date (optional)
     * @return bool True if the request has the current resource state, false if the state is outdated
     * @throws InvalidArgumentException If the Last-Modified date could not be parsed
     */
    public function hasCurrentState(RequestInterface $request, $eTag, $lastModified = null)
    {
        $ifMatch = $request->getHeaderLine('If-Match');
        if ($ifMatch) {
            if (!$this->matchesETag($eTag, $ifMatch, false)) {
                return false;
            }
        } else {
            $ifUnmodified = $request->getHeaderLine('If-Unmodified-Since');
            if ($ifUnmodified && !$this->matchesModified($lastModified, $ifUnmodified)) {
                return false;
            }
        }

        if (in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
            return true;
        }

        $ifNoneMatch = $request->getHeaderLine('If-None-Match');
        if ($ifNoneMatch && $this->matchesETag($eTag, $ifNoneMatch, true)) {
            return false;
        }

        return true;
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
     * @throws InvalidArgumentException If the current Last-Modified date could not be parsed
     */
    public function isNotModified(RequestInterface $request, ResponseInterface $response)
    {
        $noneMatch = $request->getHeaderLine('If-None-Match');
        if ($noneMatch) {
            return $this->matchesETag($response->getHeaderLine('ETag'), $noneMatch, true);
        }

        if (!in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
            return false;
        }

        $lastModified = $response->getHeaderLine('Last-Modified');
        $modifiedSince = $request->getHeaderLine('If-Modified-Since');

        return $this->matchesModified($lastModified, $modifiedSince);
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
        if (!in_array($response->getStatusCode(), [200, 203, 204, /*206,*/ 300, 301, 404, 405, 410, 414, 501], true)) {
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
     * Returns the Unix timestamp of the time parameter. The parameter can be an Unix timestamp,
     * string or a DateTime object.
     *
     * @param int|string|DateTime $time
     * @return int Unix timestamp
     * @throws InvalidArgumentException If the time could not be parsed
     */
    protected function getTimestampFromValue($time)
    {
        if (is_int($time)) {
            return $time;
        }

        if ($time instanceof DateTime) {
            return $time->getTimestamp();
        }

        if (is_string($time)) {
            return strtotime($time);
        }

        throw new InvalidArgumentException('Could not create timestamp from ' . gettype($time) . '.');
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

    /**
     * Method to check if the current ETag matches the ETag of the request.
     *
     * @link https://tools.ietf.org/html/rfc7232#section-2.3.2
     *
     * @param string $currentETag The current ETag
     * @param string $requestETags The ETags from the request
     * @param bool $weak Whether to do a weak comparison (default: strong)
     * @return bool True if the current ETag matches the ETags of the request, false otherwise
     */
    private function matchesETag($currentETag, $requestETags, $weak = false)
    {
        if ($requestETags === '*') {
            return (bool) $currentETag;
        }

        if (strpos($currentETag, 'W/"') === 0) {
            if (!$weak) {
                return false;
            }
        } else {
            $currentETag = '"' . trim($currentETag, '"') . '"';
        }

        $eTags = preg_split('/\s*,\s*/', $requestETags);
        $match = in_array($currentETag, $eTags, true);
        if (!$match && $weak) {
            $other = strpos($currentETag, '"') === 0 ? 'W/' . $currentETag : substr($currentETag, 2);
            $match = in_array($other, $eTags, true);
        }

        return $match;
    }

    /**
     * Method to check if the current Last-Modified date matches the date of the request.
     *
     * @param int|string|DateTime $currentModified Current Last-Modified date
     * @param string $requestModified Last-Modified date of the request
     * @return bool True if the current date matches the date of the request, false otherwise
     * @throws InvalidArgumentException If the current date could not be parsed
     */
    private function matchesModified($currentModified, $requestModified)
    {
        if (!$currentModified) {
            return false;
        }

        return $this->getTimestampFromValue($currentModified) <= strtotime($requestModified);
    }
}
