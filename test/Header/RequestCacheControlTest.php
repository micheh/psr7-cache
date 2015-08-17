<?php

namespace MichehTest\Cache\Header;

/**
 * @author Michel Hunziker <php@michelhunziker.com>
 * @copyright Copyright (c) 2015, Michel Hunziker <php@michelhunziker.com>
 * @license http://www.opensource.org/licenses/BSD-3-Clause The BSD-3-Clause License
 */
class RequestCacheControlTest extends CacheControlTestCase
{
    /**
     * @var string
     */
    protected $controlClass = 'Micheh\Cache\Header\RequestCacheControl';

    /**
     * @covers Micheh\Cache\Header\RequestCacheControl::withMaxStale
     */
    public function testWithMaxStale()
    {
        $control = $this->getControlWithDirective('max-stale', 10);
        $this->assertReturn($control->withMaxStale(10));
    }

    /**
     * @covers Micheh\Cache\Header\RequestCacheControl::getMaxStale
     */
    public function testGetMaxStale()
    {
        $control = $this->getControlWithGetDirective('max-stale');
        $this->assertReturn($control->getMaxStale());
    }

    /**
     * @covers Micheh\Cache\Header\RequestCacheControl::withMinFresh
     */
    public function testWithMinFresh()
    {
        $control = $this->getControlWithDirective('min-fresh', 10);
        $this->assertReturn($control->withMinFresh(10));
    }

    /**
     * @covers Micheh\Cache\Header\RequestCacheControl::getMinFresh
     */
    public function testGetMinFresh()
    {
        $control = $this->getControlWithGetDirective('min-fresh');
        $this->assertReturn($control->getMinFresh());
    }

    /**
     * @covers Micheh\Cache\Header\RequestCacheControl::withOnlyIfCached
     */
    public function testWithOnlyIfCached()
    {
        $control = $this->getControlWithFlag('only-if-cached', true);
        $this->assertReturn($control->withOnlyIfCached(true));
    }

    /**
     * @covers Micheh\Cache\Header\RequestCacheControl::hasOnlyIfCached
     */
    public function testHasOnlyIfCached()
    {
        $control = $this->getControlWithHasFlag('only-if-cached');
        $this->assertReturn($control->hasOnlyIfCached());
    }
}
