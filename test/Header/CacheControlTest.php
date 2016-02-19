<?php
/**
 * PSR-7 Cache Helpers
 *
 * @copyright Copyright (c) 2016, Michel Hunziker <php@michelhunziker.com>
 * @license http://www.opensource.org/licenses/BSD-3-Clause The BSD-3-Clause License
 */

namespace MichehTest\Cache\Header;

class CacheControlTest extends CacheControlTestCase
{
    /**
     * @var CacheControlStub
     */
    protected $cacheControl;

    /**
     * @var string
     */
    protected $controlClass = 'MichehTest\Cache\Header\CacheControlStub';

    /**
     * @covers Micheh\Cache\Header\CacheControl::withDirective
     */
    public function testWithFlag()
    {
        $clone = $this->cacheControl->withDirective('foo', true);
        $this->assertInstanceOf($this->controlClass, $clone);

        $this->assertAttributeSame([], 'directives', $this->cacheControl);
        $this->assertAttributeSame(['foo' => true], 'directives', $clone);
    }

    /**
     * @covers Micheh\Cache\Header\CacheControl::withDirective
     */
    public function testWithFlagAndFalse()
    {
        $clone = $this->cacheControl->withDirective('foo', false);
        $this->assertAttributeSame([], 'directives', $clone);
    }

    /**
     * @covers Micheh\Cache\Header\CacheControl::withDirective
     */
    public function testWithFlagRemovesFlag()
    {
        $clone = $this->cacheControl->withDirective('foo', true)->withDirective('foo', false);
        $this->assertAttributeSame([], 'directives', $clone);
    }

    /**
     * @covers Micheh\Cache\Header\CacheControl::hasDirective
     */
    public function testHasFlag()
    {
        $clone = $this->cacheControl->withDirective('foo', true);
        $this->assertTrue($clone->hasDirective('foo'));
    }

    /**
     * @covers Micheh\Cache\Header\CacheControl::hasDirective
     */
    public function testHasFlagWithoutValue()
    {
        $this->assertFalse($this->cacheControl->hasDirective('foo'));
    }

    /**
     * @covers Micheh\Cache\Header\CacheControl::withDirective
     */
    public function testWithDirective()
    {
        $clone = $this->cacheControl->withDirective('foo', 'bar');
        $this->assertInstanceOf($this->controlClass, $clone);

        $this->assertAttributeSame([], 'directives', $this->cacheControl);
        $this->assertAttributeSame(['foo' => 'bar'], 'directives', $clone);
    }

    /**
     * @covers Micheh\Cache\Header\CacheControl::withDirective
     */
    public function testWithDirectiveWithNegativeInt()
    {
        $clone = $this->cacheControl->withDirective('foo', -200);
        $this->assertAttributeSame(['foo' => 0], 'directives', $clone);
    }

    /**
     * @covers Micheh\Cache\Header\CacheControl::withDirective
     */
    public function testWithDirectiveWithNull()
    {
        $clone = $this->cacheControl->withDirective('foo', 'bar')->withDirective('foo', null);
        $this->assertAttributeSame([], 'directives', $clone);
    }

    /**
     * @covers Micheh\Cache\Header\CacheControl::getDirective
     */
    public function testGetDirective()
    {
        $clone = $this->cacheControl->withDirective('foo', 'bar');
        $this->assertSame('bar', $clone->getDirective('foo'));
    }

    /**
     * @covers Micheh\Cache\Header\CacheControl::getDirective
     */
    public function testGetDirectiveWithoutValue()
    {
        $this->assertNull($this->cacheControl->getDirective('foo'));
    }

    /**
     * @covers Micheh\Cache\Header\CacheControl::createFromString
     * @covers Micheh\Cache\Header\CacheControl::getMethod
     */
    public function testFromStringWithFlag()
    {
        $control = CacheControlStub::createFromString('no-transform');
        $this->assertAttributeSame(['no-transform' => true], 'directives', $control);
    }

    /**
     * @covers Micheh\Cache\Header\CacheControl::createFromString
     * @covers Micheh\Cache\Header\CacheControl::getMethod
     */
    public function testFromStringWithToken()
    {
        $control = CacheControlStub::createFromString('max-age=60');
        $this->assertAttributeSame(['max-age' => 60], 'directives', $control);
    }

    /**
     * @covers Micheh\Cache\Header\CacheControl::createFromString
     * @covers Micheh\Cache\Header\CacheControl::getMethod
     */
    public function testFromStringWithMultiple()
    {
        $control = CacheControlStub::createFromString('no-transform, max-age=100');
        $this->assertAttributeSame(['no-transform' => true, 'max-age' => 100], 'directives', $control);
    }

    /**
     * @covers Micheh\Cache\Header\CacheControl::createFromString
     * @covers Micheh\Cache\Header\CacheControl::getMethod
     */
    public function testFromStringWithOverrideMethod()
    {
        $this->assertSame('123', CacheControlStub::createFromString('custom=123'));
    }

    /**
     * @covers Micheh\Cache\Header\CacheControl::createFromString
     * @covers Micheh\Cache\Header\CacheControl::getMethod
     */
    public function testFromStringWithUnknownDirective()
    {
        $control = CacheControlStub::createFromString('foo="bar"');
        $this->assertAttributeSame(['foo' => 'bar'], 'directives', $control);
    }

    /**
     * @covers Micheh\Cache\Header\CacheControl::createFromString
     * @covers Micheh\Cache\Header\CacheControl::getMethod
     */
    public function testFromStringWithUnknownDirectiveFlag()
    {
        $control = CacheControlStub::createFromString('foo');
        $this->assertAttributeSame([], 'directives', $control);
    }

    /**
     * @covers Micheh\Cache\Header\CacheControl::withMaxAge
     */
    public function testWithMaxAge()
    {
        $control = $this->getControlWithDirective('max-age', 5);
        $this->assertReturn($control->withMaxAge('5'));
    }

    /**
     * @covers Micheh\Cache\Header\CacheControl::getMaxAge
     */
    public function testGetMaxAge()
    {
        $control = $this->getControlWithGetDirective('max-age');
        $this->assertReturn($control->getMaxAge());
    }

    /**
     * @covers Micheh\Cache\Header\CacheControl::withNoCache
     */
    public function testWithNoCache()
    {
        $control = $this->getControlWithDirective('no-cache', true);
        $this->assertReturn($control->withNoCache(true));
    }

    /**
     * @covers Micheh\Cache\Header\CacheControl::hasNoCache
     */
    public function testHasNoCache()
    {
        $control = $this->getControlWithHasFlag('no-cache');
        $this->assertReturn($control->hasNoCache());
    }

    /**
     * @covers Micheh\Cache\Header\CacheControl::withNoStore
     */
    public function testWithNoStore()
    {
        $control = $this->getControlWithDirective('no-store', true);
        $this->assertReturn($control->withNoStore(true));
    }

    /**
     * @covers Micheh\Cache\Header\CacheControl::hasNoStore
     */
    public function testHasNoStore()
    {
        $control = $this->getControlWithHasFlag('no-store');
        $this->assertReturn($control->hasNoStore());
    }

    /**
     * @covers Micheh\Cache\Header\CacheControl::withNoTransform
     */
    public function testWithNoTransform()
    {
        $control = $this->getControlWithDirective('no-transform', true);
        $this->assertReturn($control->withNoTransform(true));
    }

    /**
     * @covers Micheh\Cache\Header\CacheControl::hasNoTransform
     */
    public function testHasNoTransform()
    {
        $control = $this->getControlWithHasFlag('no-transform');
        $this->assertReturn($control->hasNoTransform());
    }

    /**
     * @covers Micheh\Cache\Header\CacheControl::withExtension
     */
    public function testWithExtension()
    {
        $control = $this->getControlWithDirective('foo', 'bar');
        $this->assertReturn($control->withExtension('foo', '"bar"'));
    }

    /**
     * @covers Micheh\Cache\Header\CacheControl::withExtension
     */
    public function testWithExtensionInvalidType()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Name and value of the extension have to be a string.'
        );
        $this->cacheControl->withExtension('foo', true);
    }

    /**
     * @covers Micheh\Cache\Header\CacheControl::getExtension
     */
    public function testGetExtension()
    {
        $control = $this->getControlWithGetDirective('foo');
        $this->assertReturn($control->getExtension('foo'));
    }

    /**
     * @covers Micheh\Cache\Header\CacheControl::__toString
     */
    public function testToStringWithFlag()
    {
        $clone = $this->cacheControl->withDirective('foo', true);
        $this->assertSame('foo', (string) $clone);
    }

    /**
     * @covers Micheh\Cache\Header\CacheControl::__toString
     */
    public function testToStringWithToken()
    {
        $clone = $this->cacheControl->withDirective('foo', 30);
        $this->assertSame('foo=30', (string) $clone);
    }

    /**
     * @covers Micheh\Cache\Header\CacheControl::__toString
     */
    public function testToStringWithExtension()
    {
        $clone = $this->cacheControl->withDirective('foo', 'bar');
        $this->assertSame('foo="bar"', (string) $clone);
    }

    /**
     * @covers Micheh\Cache\Header\CacheControl::__toString
     */
    public function testToStringWithMultiple()
    {
        $clone = $this->cacheControl->withDirective('public', true)->withDirective('foo', 20);
        $this->assertSame('public, foo=20', (string) $clone);
    }

    /**
     * @covers Micheh\Cache\Header\CacheControl::__toString
     */
    public function testToStringWithEmpty()
    {
        $this->assertSame('', (string) $this->cacheControl);
    }
}
