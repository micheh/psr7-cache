<?php

namespace Micheh\Cache\Header;

/**
 * Cache-Control header for a request.
 *
 * @author Michel Hunziker <php@michelhunziker.com>
 * @copyright Copyright (c) 2015, Michel Hunziker <php@michelhunziker.com>
 * @license http://www.opensource.org/licenses/BSD-3-Clause The BSD-3-Clause License
 */
class RequestCacheControl extends CacheControl
{
    /**
     * {@inheritdoc}
     */
    protected static $directiveMethods = [
        'max-stale' => 'withMaxStale',
        'min-fresh' => 'withMinFresh',
        'only-if-cached' => 'withOnlyIfCached',
    ];

    /**
     * Set how many seconds a stale representation is acceptable.
     *
     * @param int $seconds
     * @return static
     */
    public function withMaxStale($seconds)
    {
        return $this->withDirective('max-stale', (int) $seconds);
    }

    /**
     * @return int|null
     */
    public function getMaxStale()
    {
        return $this->getDirective('max-stale');
    }

    /**
     * Set how many seconds the representation should still be fresh.
     *
     * @param int $seconds
     * @return static
     */
    public function withMinFresh($seconds)
    {
        return $this->withDirective('min-fresh', (int) $seconds);
    }

    /**
     * @return int|null
     */
    public function getMinFresh()
    {
        return $this->getDirective('min-fresh');
    }

    /**
     * Set whether only a stored response should be returned.
     *
     * @param bool $flag
     * @return static
     */
    public function withOnlyIfCached($flag = true)
    {
        return $this->withFlag('only-if-cached', $flag);
    }

    /**
     * @return bool
     */
    public function hasOnlyIfCached()
    {
        return $this->hasFlag('only-if-cached');
    }
}