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
        return $this->withPublicPrivate(true, $flag);
    }

    /**
     * @return bool
     */
    public function isPublic()
    {
        return $this->hasDirective('public');
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
        return $this->withPublicPrivate(false, $flag);
    }

    /**
     * @return bool
     */
    public function isPrivate()
    {
        return $this->hasDirective('private');
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
        return $this->withDirective('must-revalidate', (bool) $flag);
    }

    /**
     * @return bool
     */
    public function hasMustRevalidate()
    {
        return $this->hasDirective('must-revalidate');
    }

    /**
     * Set whether a public cache should validate a stale representation.
     *
     * @param bool $flag
     * @return static
     */
    public function withProxyRevalidate($flag = true)
    {
        return $this->withDirective('proxy-revalidate', (bool) $flag);
    }

    /**
     * @return bool
     */
    public function hasProxyRevalidate()
    {
        return $this->hasDirective('proxy-revalidate');
    }

    /**
     * Sets the flag for the public and private directives.
     *
     * @param bool $isPublic
     * @param bool $flag
     * @return static
     */
    private function withPublicPrivate($isPublic, $flag)
    {
        $type = $isPublic ? 'public' : 'private';
        $otherType = $isPublic ? 'private' : 'public';

        $clone = $this->withDirective($type, (bool) $flag);
        if ($flag && $clone->hasDirective($otherType)) {
            $clone = $clone->withDirective($otherType, false);
        }

        return $clone;
    }
}
