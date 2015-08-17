<?php

namespace Micheh\Cache\Header;

/**
 * Cache-Control header for a response.
 *
 * @author Michel Hunziker <php@michelhunziker.com>
 * @copyright Copyright (c) 2015, Michel Hunziker <php@michelhunziker.com>
 * @license http://www.opensource.org/licenses/BSD-3-Clause The BSD-3-Clause License
 */
class ResponseCacheControl extends CacheControl
{
    /**
     * {@inheritdoc}
     */
    protected static $directiveMethods = [
        'public' => 'withPublic',
        'private' => 'withPrivate',
        's-maxage' => 'withSharedMaxAge',
        'stale-while-revalidate' => 'withStaleWhileRevalidate',
        'stale-if-error' => 'withStaleIfError',
        'must-revalidate' => 'withMustRevalidate',
        'proxy-revalidate' => 'withProxyRevalidate',
    ];

    /**
     * Set whether a response should be cached by shared caches. The method will automatically
     * remove the private flag if it is set.
     *
     * @param bool $flag
     * @return static
     */
    public function withPublic($flag = true)
    {
        $clone = $this->withFlag('public', $flag);
        if ($flag && $clone->hasFlag('private')) {
            $clone = $clone->withFlag('private', false);
        }

        return $clone;
    }

    /**
     * @return bool
     */
    public function isPublic()
    {
        return $this->hasFlag('public');
    }

    /**
     * Set whether a response should be private (only cacheable by the client who made the request.
     * The method will automatically remove the public flag if it is set.
     *
     * @param bool $flag
     * @return static
     */
    public function withPrivate($flag = true)
    {
        $clone = $this->withFlag('private', $flag);
        if ($flag && $clone->hasFlag('public')) {
            $clone = $clone->withFlag('public', false);
        }

        return $clone;
    }

    /**
     * @return bool
     */
    public function isPrivate()
    {
        return $this->hasFlag('private');
    }

    /**
     * Set how many seconds shared caches should cache the response. Use this directive only if it
     * is different than the max age value.
     *
     * @param int $seconds
     * @return static
     */
    public function withSharedMaxAge($seconds)
    {
        return $this->withDirective('s-maxage', (int) $seconds);
    }

    /**
     * @return int|null
     */
    public function getSharedMaxAge()
    {
        return $this->getDirective('s-maxage');
    }

    /**
     * Set how many seconds a stale representation can be used while revalidating in the background.
     *
     * @param int $seconds
     * @return static
     */
    public function withStaleWhileRevalidate($seconds)
    {
        return $this->withDirective('stale-while-revalidate', (int) $seconds);
    }

    /**
     * @return int|null
     */
    public function getStaleWhileRevalidate()
    {
        return $this->getDirective('stale-while-revalidate');
    }

    /**
     * Set how many seconds a stale representation can be used in the case of a server error.
     *
     * @param int $seconds
     * @return static
     */
    public function withStaleIfError($seconds)
    {
        return $this->withDirective('stale-if-error', (int) $seconds);
    }

    /**
     * @return int|null
     */
    public function getStaleIfError()
    {
        return $this->getDirective('stale-if-error');
    }

    /**
     * Set whether a stale representation should be validated.
     *
     * @param bool $flag
     * @return static
     */
    public function withMustRevalidate($flag = true)
    {
        return $this->withFlag('must-revalidate', $flag);
    }

    /**
     * @return bool
     */
    public function hasMustRevalidate()
    {
        return $this->hasFlag('must-revalidate');
    }

    /**
     * Set whether a public cache should validate a stale representation.
     *
     * @param bool $flag
     * @return static
     */
    public function withProxyRevalidate($flag = true)
    {
        return $this->withFlag('proxy-revalidate', $flag);
    }

    /**
     * @return bool
     */
    public function hasProxyRevalidate()
    {
        return $this->hasFlag('proxy-revalidate');
    }
}
