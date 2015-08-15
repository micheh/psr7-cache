<?php

namespace MichehTest\Cache;

use DateTime;
use DateTimeZone;
use Micheh\Cache\CacheUtil;
use Psr\Http\Message\ResponseInterface;
use ReflectionMethod;

/**
 * Test case for Micheh\Cache\CacheUtil.
 *
 * @author Michel Hunziker <php@michelhunziker.com>
 * @copyright Copyright (c) 2015, Michel Hunziker <php@michelhunziker.com>
 * @license http://www.opensource.org/licenses/BSD-3-Clause The BSD-3-Clause License
 */
class CacheUtilTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CacheUtil
     */
    protected $cacheUtil;

    protected function setUp()
    {
        $this->cacheUtil = new CacheUtil();
    }

    /**
     * @covers Micheh\Cache\CacheUtil::withCache
     */
    public function testWithCache()
    {
        $util = $this->getMock('Micheh\Cache\CacheUtil', ['withCacheControl']);
        $response = $this->getMock('Psr\Http\Message\ResponseInterface');

        $util->expects($this->once())->method('withCacheControl')
            ->with($response, ['type' => 'private', 'max-age' => 600])
            ->will($this->returnValue('phpunit'));

        $return = $util->withCache($response);
        $this->assertEquals('phpunit', $return);
    }

    /**
     * @covers Micheh\Cache\CacheUtil::withCache
     */
    public function testWithCacheCustomParameters()
    {
        $util = $this->getMock('Micheh\Cache\CacheUtil', ['withCacheControl']);
        $response = $this->getMock('Psr\Http\Message\ResponseInterface');

        $util->expects($this->once())->method('withCacheControl')
            ->with($response, ['type' => 'public', 'max-age' => 86400]);

        $util->withCache($response, 'public', 86400);
    }

    /**
     * @covers Micheh\Cache\CacheUtil::withCachePrevention
     */
    public function testWithCachePrevention()
    {
        $util = $this->getMock('Micheh\Cache\CacheUtil', ['withCacheControl']);
        $response = $this->getMock('Psr\Http\Message\ResponseInterface');

        $util->expects($this->once())->method('withCacheControl')
            ->with($response, ['no-cache' => true, 'no-store' => true, 'must-revalidate' => true])
            ->will($this->returnValue('phpunit'));

        $return = $util->withCachePrevention($response);
        $this->assertEquals('phpunit', $return);
    }

    /**
     * @covers Micheh\Cache\CacheUtil::withCacheControl
     */
    public function testWithCacheControl()
    {
        $response = $this->getResponse('Cache-Control', 'public, max-age=600');

        $return = $this->cacheUtil->withCacheControl($response, ['type' => 'public', 'max-age' => 600]);
        $this->assertEquals('phpunit', $return);
    }

    /**
     * @covers Micheh\Cache\CacheUtil::withCacheControl
     */
    public function testWithCacheControlInvalidType()
    {
        $response = $this->getMock('Psr\Http\Message\ResponseInterface');

        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid cache control type "foo", valid values are "public" and "private".'
        );
        $this->cacheUtil->withCacheControl($response, ['type' => 'foo']);
    }

    /**
     * @covers Micheh\Cache\CacheUtil::withCacheControl
     */
    public function testWithCacheControlInvalidDirective()
    {
        $response = $this->getMock('Psr\Http\Message\ResponseInterface');

        $this->setExpectedException(
            'InvalidArgumentException',
            'Unknown cache control directive: foo'
        );
        $this->cacheUtil->withCacheControl($response, ['foo' => 'bar']);
    }

    /**
     * @covers Micheh\Cache\CacheUtil::withCacheControl
     */
    public function testWithCacheControlFlag()
    {
        $response = $this->getResponse('Cache-Control', 'must-revalidate');

        $return = $this->cacheUtil->withCacheControl(
            $response,
            ['must-revalidate' => true, 'no-cache' => false]
        );
        $this->assertEquals('phpunit', $return);
    }

    /**
     * @covers Micheh\Cache\CacheUtil::withExpires
     * @covers Micheh\Cache\CacheUtil::getTimeFromValue
     */
    public function testWithExpires()
    {
        $date = new DateTime('2015-08-10 18:30:12', new DateTimeZone('UTC'));
        $response = $this->getResponse('Expires', 'Mon, 10 Aug 2015 18:30:12 GMT');

        $return = $this->cacheUtil->withExpires($response, $date->getTimestamp());
        $this->assertEquals('phpunit', $return);
    }

    /**
     * @covers Micheh\Cache\CacheUtil::withExpires
     * @covers Micheh\Cache\CacheUtil::getTimeFromValue
     */
    public function testWithExpiresString()
    {
        $response = $this->getResponse('Expires', 'Mon, 10 Aug 2015 18:30:12 GMT');

        $return = $this->cacheUtil->withExpires($response, '2015-08-10 18:30:12');
        $this->assertEquals('phpunit', $return);
    }

    /**
     * @covers Micheh\Cache\CacheUtil::withExpires
     * @covers Micheh\Cache\CacheUtil::getTimeFromValue
     */
    public function testWithExpiresDateTime()
    {
        $date = new DateTime('2015-08-10 18:30:12', new DateTimeZone('UTC'));
        $response = $this->getResponse('Expires', 'Mon, 10 Aug 2015 18:30:12 GMT');

        $return = $this->cacheUtil->withExpires($response, $date);
        $this->assertEquals('phpunit', $return);
    }

    /**
     * @covers Micheh\Cache\CacheUtil::withExpires
     * @covers Micheh\Cache\CacheUtil::getTimeFromValue
     */
    public function testWithRelativeTime()
    {
        $date = new DateTime('@' . (time() + 300), new DateTimeZone('UTC'));
        $response = $this->getResponse('Expires', $date->format('D, d M Y H:i:s') . ' GMT');

        $return = $this->cacheUtil->withExpires($response, 300, true);
        $this->assertEquals('phpunit', $return);
    }

    /**
     * @covers Micheh\Cache\CacheUtil::withETag
     */
    public function testWithETag()
    {
        $response = $this->getResponse('ETag', '"foo"');

        $return = $this->cacheUtil->withETag($response, 'foo');
        $this->assertEquals('phpunit', $return);
    }

    /**
     * @covers Micheh\Cache\CacheUtil::withETag
     */
    public function testWithETagWeak()
    {
        $response = $this->getResponse('ETag', 'W/"foo"');

        $return = $this->cacheUtil->withETag($response, 'foo', true);
        $this->assertEquals('phpunit', $return);
    }

    /**
     * @covers Micheh\Cache\CacheUtil::withETag
     */
    public function testWithETagAlreadyQuoted()
    {
        $response = $this->getResponse('ETag', '"foo"');

        $return = $this->cacheUtil->withETag($response, '"foo"');
        $this->assertEquals('phpunit', $return);
    }

    /**
     * @covers Micheh\Cache\CacheUtil::withLastModified
     * @covers Micheh\Cache\CacheUtil::getTimeFromValue
     */
    public function testWithLastModified()
    {
        $date = new DateTime('2015-08-10 18:30:12', new DateTimeZone('UTC'));
        $response = $this->getResponse('Last-Modified', 'Mon, 10 Aug 2015 18:30:12 GMT');

        $return = $this->cacheUtil->withLastModified($response, $date);
        $this->assertEquals('phpunit', $return);
    }

    /**
     * @covers Micheh\Cache\CacheUtil::getTimeFromValue
     */
    public function testGetTimeFromValueInvalidType()
    {
        $method = new ReflectionMethod('Micheh\Cache\CacheUtil', 'getTimeFromValue');
        $method->setAccessible(true);

        $this->setExpectedException(
            'InvalidArgumentException',
            'Could not create a valid date from string.'
        );
        $method->invoke($this->cacheUtil, 'foo');
    }

    /**
     * @param string $expectedHeader
     * @param string $expectedValue
     * @return \PHPUnit_Framework_MockObject_MockObject|ResponseInterface
     */
    protected function getResponse($expectedHeader, $expectedValue)
    {
        $response = $this->getMock('Psr\Http\Message\ResponseInterface');
        $response->expects($this->once())->method('withHeader')
            ->with($expectedHeader, $expectedValue)
            ->will($this->returnValue('phpunit'));

        return $response;
    }
}
