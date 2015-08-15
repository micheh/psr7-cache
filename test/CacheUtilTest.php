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
        $response = $this->getResponse();

        $util->expects($this->once())->method('withCacheControl')
            ->with($response, ['type' => 'private', 'max-age' => 600])
            ->willReturn('phpunit');

        $return = $util->withCache($response);
        $this->assertEquals('phpunit', $return);
    }

    /**
     * @covers Micheh\Cache\CacheUtil::withCache
     */
    public function testWithCacheCustomParameters()
    {
        $util = $this->getMock('Micheh\Cache\CacheUtil', ['withCacheControl']);
        $response = $this->getResponse();

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
        $response = $this->getResponse();

        $util->expects($this->once())->method('withCacheControl')
            ->with($response, ['no-cache' => true, 'no-store' => true, 'must-revalidate' => true])
            ->willReturn('phpunit');

        $return = $util->withCachePrevention($response);
        $this->assertEquals('phpunit', $return);
    }

    /**
     * @covers Micheh\Cache\CacheUtil::withCacheControl
     */
    public function testWithCacheControl()
    {
        $response = $this->getResponseWithExpectedHeader('Cache-Control', 'public, max-age=600');

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
        $response = $this->getResponseWithExpectedHeader('Cache-Control', 'must-revalidate');

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
        $response = $this->getResponseWithExpectedHeader('Expires', 'Mon, 10 Aug 2015 18:30:12 GMT');

        $return = $this->cacheUtil->withExpires($response, $date->getTimestamp());
        $this->assertEquals('phpunit', $return);
    }

    /**
     * @covers Micheh\Cache\CacheUtil::withExpires
     * @covers Micheh\Cache\CacheUtil::getTimeFromValue
     */
    public function testWithExpiresString()
    {
        $response = $this->getResponseWithExpectedHeader('Expires', 'Mon, 10 Aug 2015 18:30:12 GMT');

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
        $response = $this->getResponseWithExpectedHeader('Expires', 'Mon, 10 Aug 2015 18:30:12 GMT');

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
        $response = $this->getResponseWithExpectedHeader('Expires', $date->format('D, d M Y H:i:s') . ' GMT');

        $return = $this->cacheUtil->withExpires($response, 300, true);
        $this->assertEquals('phpunit', $return);
    }

    /**
     * @covers Micheh\Cache\CacheUtil::withETag
     */
    public function testWithETag()
    {
        $response = $this->getResponseWithExpectedHeader('ETag', '"foo"');

        $return = $this->cacheUtil->withETag($response, 'foo');
        $this->assertEquals('phpunit', $return);
    }

    /**
     * @covers Micheh\Cache\CacheUtil::withETag
     */
    public function testWithETagWeak()
    {
        $response = $this->getResponseWithExpectedHeader('ETag', 'W/"foo"');

        $return = $this->cacheUtil->withETag($response, 'foo', true);
        $this->assertEquals('phpunit', $return);
    }

    /**
     * @covers Micheh\Cache\CacheUtil::withETag
     */
    public function testWithETagAlreadyQuoted()
    {
        $response = $this->getResponseWithExpectedHeader('ETag', '"foo"');

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
        $response = $this->getResponseWithExpectedHeader('Last-Modified', 'Mon, 10 Aug 2015 18:30:12 GMT');

        $return = $this->cacheUtil->withLastModified($response, $date);
        $this->assertEquals('phpunit', $return);
    }

    /**
     * @covers Micheh\Cache\CacheUtil::isNotModified
     * @dataProvider eTags
     * @param string $ifNoneMatch
     * @param string $eTag
     * @param bool $notModified
     */
    public function testIsNotModifiedWithETag($ifNoneMatch, $eTag, $notModified)
    {
        $request = $this->getMock('Psr\Http\Message\RequestInterface');
        $request->expects($this->once())->method('getHeaderLine')
            ->with('If-None-Match')->will($this->returnValue($ifNoneMatch));

        $response = $this->getResponseWithHeader('ETag', $eTag);

        $result = $this->cacheUtil->isNotModified($request, $response);
        $this->assertSame($notModified, $result);
    }

    /**
     * @return array
     */
    public function eTags()
    {
        return [
            'not-modified' => ['"foo"', '"foo"', true],
            'modified' => ['"bar"', '"foo"', false],
            'not-modified-multiple' => ['"foo", "bar"', '"bar"', true],
            'modified-multiple' => ['"foo", "bar"', '"baz"', false],
            'star' => ['*', '"foo"', true],
        ];
    }

    /**
     * @covers Micheh\Cache\CacheUtil::isNotModified
     * @dataProvider lastModified
     * @param string $ifModifiedSince
     * @param string $lastModified
     * @param bool $notModified
     */
    public function testIsNotModifiedWithModified($ifModifiedSince, $lastModified, $notModified)
    {
        $request = $this->getMock('Psr\Http\Message\RequestInterface');
        $request->expects($this->at(2))->method('getHeaderLine')
            ->with('If-Modified-Since')->will($this->returnValue($ifModifiedSince));

        $request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));

        $response = $this->getResponseWithHeader('Last-Modified', $lastModified, 1);

        $result = $this->cacheUtil->isNotModified($request, $response);
        $this->assertSame($notModified, $result);
    }

    /**
     * @return array
     */
    public function lastModified()
    {
        return [
            'not-modified' => ['Mon, 10 Aug 2015 18:30:12 GMT', 'Mon, 10 Aug 2015 18:30:12 GMT', true],
            'not-modified-server-older' => ['Mon, 10 Aug 2015 22:30:12 GMT', 'Mon, 10 Aug 2015 18:30:12 GMT', true],
            'modified' => ['Mon, 10 Aug 2015 11:30:12 GMT', 'Mon, 10 Aug 2015 18:30:12 GMT', false],
        ];
    }

    /**
     * @covers Micheh\Cache\CacheUtil::isNotModified
     */
    public function testIsNotModifiedWithModifiedUnsafe()
    {
        $request = $this->getMock('Psr\Http\Message\RequestInterface');
        $request->expects($this->once())->method('getMethod')->will($this->returnValue('POST'));

        $response = $this->getResponse();
        $this->assertFalse($this->cacheUtil->isNotModified($request, $response));
    }

    /**
     * @covers Micheh\Cache\CacheUtil::isCacheable
     */
    public function testIsCacheable()
    {
        /** @var CacheUtil|\PHPUnit_Framework_MockObject_MockObject $util */
        $util = $this->getMock('Micheh\Cache\CacheUtil', ['isFresh']);

        $response = $this->getResponseWithHeader('Cache-Control', 'public', 1);
        $response->expects($this->once())->method('getStatusCode')
            ->willReturn(200);

        $util->expects($this->once())->method('isFresh')
            ->with($response)->willReturn('phpunit');

        $this->assertEquals('phpunit', $util->isCacheable($response));
    }

    /**
     * @covers Micheh\Cache\CacheUtil::isCacheable
     */
    public function testIsCacheableWithPrivate()
    {
        $response = $this->getResponseWithHeader('Cache-Control', 'private', 1);
        $response->expects($this->once())->method('getStatusCode')
            ->willReturn(200);

        $this->assertFalse($this->cacheUtil->isCacheable($response));
    }

    /**
     * @covers Micheh\Cache\CacheUtil::isCacheable
     */
    public function testIsCacheableWithUncacheableStatus()
    {
        $response = $this->getResponse();
        $response->expects($this->once())->method('getStatusCode')
            ->willReturn(500);

        $this->assertFalse($this->cacheUtil->isCacheable($response));
    }

    /**
     * @covers Micheh\Cache\CacheUtil::isFresh
     */
    public function testIsFresh()
    {
        $response = $this->getResponse();

        /** @var CacheUtil|\PHPUnit_Framework_MockObject_MockObject $util */
        $util = $this->getMock('Micheh\Cache\CacheUtil', ['getLifetime', 'getAge']);
        $util->expects($this->once())->method('getLifetime')
            ->with($response)->willReturn(20);

        $util->expects($this->once())->method('getAge')
            ->with($response)->willReturn(10);

        $this->assertTrue($util->isFresh($response));
    }

    /**
     * @covers Micheh\Cache\CacheUtil::isFresh
     */
    public function testIsFreshWithOlderAge()
    {
        $response = $this->getResponse();

        /** @var CacheUtil|\PHPUnit_Framework_MockObject_MockObject $util */
        $util = $this->getMock('Micheh\Cache\CacheUtil', ['getLifetime', 'getAge']);
        $util->expects($this->once())->method('getLifetime')
            ->with($response)->willReturn(20);

        $util->expects($this->once())->method('getAge')
            ->with($response)->willReturn(30);

        $this->assertFalse($util->isFresh($response));
    }

    /**
     * @covers Micheh\Cache\CacheUtil::isFresh
     */
    public function testIsFreshWithoutLifetime()
    {
        $response = $this->getResponse();

        /** @var CacheUtil|\PHPUnit_Framework_MockObject_MockObject $util */
        $util = $this->getMock('Micheh\Cache\CacheUtil', ['getLifetime']);
        $util->expects($this->once())->method('getLifetime')
            ->with($response)->willReturn(null);

        $this->assertFalse($util->isFresh($response));
    }

    /**
     * @covers Micheh\Cache\CacheUtil::getLifetime
     * @covers Micheh\Cache\CacheUtil::getTokenValue
     */
    public function testGetLifetime()
    {
        $response = $this->getResponseWithHeader('Cache-Control', 'max-age=60, s-maxage=200');
        $this->assertSame(200, $this->cacheUtil->getLifetime($response));
    }

    /**
     * @covers Micheh\Cache\CacheUtil::getLifetime
     * @covers Micheh\Cache\CacheUtil::getTokenValue
     */
    public function testGetLifetimeWithoutSharedAge()
    {
        $response = $this->getResponseWithHeader('Cache-Control', 'max-age=60, public');
        $this->assertSame(60, $this->cacheUtil->getLifetime($response));
    }

    /**
     * @covers Micheh\Cache\CacheUtil::getLifetime
     * @covers Micheh\Cache\CacheUtil::getTokenValue
     */
    public function testGetLifetimeWithExpires()
    {
        $response = $this->getResponseWithHeader('Expires', date('D, d M Y H:i:s', time() + 20), 1);
        $this->assertSame(20, $this->cacheUtil->getLifetime($response));
    }

    /**
     * @covers Micheh\Cache\CacheUtil::getLifetime
     */
    public function testGetLifetimeWithoutAnything()
    {
        $response = $this->getResponse();
        $this->assertNull($this->cacheUtil->getLifetime($response));
    }

    /**
     * @covers Micheh\Cache\CacheUtil::getAge
     */
    public function testGetAge()
    {
        $response = $this->getResponseWithHeader('Age', '5');
        $this->assertSame(5, $this->cacheUtil->getAge($response));
    }

    /**
     * @covers Micheh\Cache\CacheUtil::getAge
     */
    public function testGetAgeWithDate()
    {
        $response = $this->getResponseWithHeader('Date', date('D, d M Y H:i:s', time() - 20), 1);
        $response->expects($this->at(0))->method('getHeaderLine')
            ->with('Age')->willReturn('');

        $this->assertSame(20, $this->cacheUtil->getAge($response));
    }

    /**
     * @covers Micheh\Cache\CacheUtil::getAge
     */
    public function testGetAgeWithoutHeaders()
    {
        $response = $this->getResponseWithHeader('Age', '');
        $response->expects($this->at(1))->method('getHeaderLine')
            ->with('Date')->willReturn('');

        $this->assertNull($this->cacheUtil->getAge($response));
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
    protected function getResponseWithExpectedHeader($expectedHeader, $expectedValue)
    {
        $response = $this->getResponse();
        $response->expects($this->once())->method('withHeader')
            ->with($expectedHeader, $expectedValue)
            ->willReturn('phpunit');

        return $response;
    }

    /**
     * @param string $header
     * @param string $value
     * @param int $index
     * @return \PHPUnit_Framework_MockObject_MockObject|ResponseInterface
     */
    protected function getResponseWithHeader($header, $value, $index = 0)
    {
        $response = $this->getResponse();
        $response->expects($this->at($index))->method('getHeaderLine')
            ->with($header)->willReturn($value);

        return $response;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ResponseInterface
     */
    protected function getResponse()
    {
        return $this->getMock('Psr\Http\Message\ResponseInterface');
    }
}
