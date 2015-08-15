<?php

namespace Micheh\Cache;

use DateTime;
use DateTimeZone;
use Exception;
use InvalidArgumentException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Util class to add cache headers to PSR-7 HTTP messages.
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
     * - `type`: public or private. Use public to enable shared caches (Request only).
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

        return $value  . ' GMT';
    }
}
