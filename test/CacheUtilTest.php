<?php
/**
 * PSR-7 Cache Helpers
 *
 * @copyright Copyright (c) 2016, Michel Hunziker <php@michelhunziker.com>
 * @license http://www.opensource.org/licenses/BSD-3-Clause The BSD-3-Clause License
 */

namespace MichehTest\Cache;

use DateTime;
use DateTimeZone;
use Micheh\Cache\CacheUtil;
use Micheh\Cache\Header\ResponseCacheControl;
use Psr\Http\Message\ResponseInterface;
use ReflectionMethod;

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
            ->with($response, 'private, max-age=600')
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
            ->with($response, 'public, max-age=86400');

        $util->withCache($response, true, 86400);
    }

    /**
     * @covers Micheh\Cache\CacheUtil::withCachePrevention
     */
    public function testWithCachePrevention()
    {
        $util = $this->getMock('Micheh\Cache\CacheUtil', ['withCacheControl']);
        $response = $this->getResponse();

        $util->expects($this->once())->method('withCacheControl')
            ->with($response, 'no-cache, no-store, must-revalidate')
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

        $cacheControl = new ResponseCacheControl();
        $cacheControl = $cacheControl->withPublic()->withMaxAge(600);

        $return = $this->cacheUtil->withCacheControl($response, $cacheControl);
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
        $response = $this->getResponseWithExpectedHeader('Expires', 'Mon, 10 Aug 2015 16:30:12 GMT');

        $timezone = ini_get('date.timezone');
        ini_set('date.timezone', 'UTC');

        $return = $this->cacheUtil->withExpires($response, '2015-08-10 16:30:12');

        ini_set('date.timezone', $timezone);

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
     * @covers Micheh\Cache\CacheUtil::withRelativeExpires
     * @covers Micheh\Cache\CacheUtil::getTimeFromValue
     */
    public function testWithRelativeExpires()
    {
        $date = gmdate('D, d M Y H:i:s', time() + 300) . ' GMT';
        $response = $this->getResponseWithExpectedHeader('Expires', $date);

        $return = $this->cacheUtil->withRelativeExpires($response, 300);
        $this->assertEquals('phpunit', $return);
    }


    /**
     * @covers Micheh\Cache\CacheUtil::withRelativeExpires
     */
    public function testWithRelativeExpiresAndString()
    {
        $response = $this->getResponse();

        $this->setExpectedException(
            'InvalidArgumentException',
            'Expected an integer with the number of seconds, received string.'
        );
        $this->cacheUtil->withRelativeExpires($response, 'now');
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
     * @covers Micheh\Cache\CacheUtil::hasStateValidator
     * @dataProvider stateValidators
     * @param string $ifMatch
     * @param string $ifUnmodified
     * @param bool $hasValidator
     */
    public function testHasStateValidator($ifMatch, $ifUnmodified, $hasValidator)
    {
        $map = [
            ['If-Match', $ifMatch],
            ['If-Unmodified-Since', $ifUnmodified]
        ];

        $request = $this->getMock('Psr\Http\Message\RequestInterface');
        $request->method('hasHeader')->willReturnMap($map);

        $result = $this->cacheUtil->hasStateValidator($request);
        $this->assertSame($hasValidator, $result);
    }

    /**
     * @return array
     */
    public function stateValidators()
    {
        return [
            'none' => [false, false, false],
            'if-match' => [true, false, true],
            'if-unmodified' => [false, true, true],
            'both' => [true, true, true],
        ];
    }

    /**
     * @covers Micheh\Cache\CacheUtil::hasCurrentState
     * @covers Micheh\Cache\CacheUtil::matchesETag
     * @dataProvider currentStateETags
     * @param string $ifMatch
     * @param string $eTag
     * @param bool $isCurrent
     */
    public function testHasCurrentStateWithETag($ifMatch, $eTag, $isCurrent)
    {
        $map = [
            ['If-Match', $ifMatch],
            ['If-None-Match', '']
        ];

        $request = $this->getMock('Psr\Http\Message\RequestInterface');
        $request->method('getHeaderLine')->willReturnMap($map);


        $result = $this->cacheUtil->hasCurrentState($request, $eTag);
        $this->assertSame($isCurrent, $result);
    }

    /**
     * @return array
     */
    public function currentStateETags()
    {
        return [
            'current' => ['"foo"', 'foo', true],
            'current-quoted' => ['"foo"', '"foo"', true],
            'not-current' => ['"foo"', 'bar', false],
            'not-current-quoted' => ['"foo"', '"bar"', false],
            'current-multiple' => ['"foo", "bar"', 'bar', true],
            'not-current-multiple' => ['"foo", "bar"', 'baz', false],
            'star' => ['*', 'baz', true],
            'star-without-current' => ['*', null, false],
            'weak-client' => ['W/"foo"', 'foo', false],
            'weak-server' => ['"foo"', 'W/"foo"', false],
            'weak-both' => ['W/"foo"', 'W/"foo"', false],
        ];
    }

    /**
     * @covers Micheh\Cache\CacheUtil::hasCurrentState
     * @covers Micheh\Cache\CacheUtil::matchesModified
     * @dataProvider currentTimes
     * @param string $ifUnmodified
     * @param string $lastModified
     * @param bool $isCurrent
     */
    public function testHasCurrentStateWithModified($ifUnmodified, $lastModified, $isCurrent)
    {
        $map = [
            ['If-Match', ''],
            ['If-Unmodified-Since', $ifUnmodified],
            ['If-None-Match', '']
        ];

        $request = $this->getMock('Psr\Http\Message\RequestInterface');
        $request->method('getHeaderLine')->willReturnMap($map);


        $result = $this->cacheUtil->hasCurrentState($request, null, $lastModified);
        $this->assertSame($isCurrent, $result);
    }

    /**
     * @return array
     */
    public function currentTimes()
    {
        return [
            'current-equal' => ['Mon, 10 Aug 2015 18:30:12 GMT', 'Mon, 10 Aug 2015 18:30:12 GMT', true],
            'current-server-earlier' => ['Mon, 10 Aug 2015 20:30:12 GMT', 'Mon, 10 Aug 2015 18:30:12 GMT', true],
            'not-current-client-earlier' => ['Mon, 10 Aug 2015 16:30:12 GMT', 'Mon, 10 Aug 2015 18:30:12 GMT', false],
            'without-current' => ['Mon, 10 Aug 2015 18:30:12 GMT', null, false],
        ];
    }

    /**
     * @covers Micheh\Cache\CacheUtil::hasCurrentState
     * @covers Micheh\Cache\CacheUtil::matchesETag
     * @dataProvider currentStateNoneMatches
     * @param string $ifNoneMatch
     * @param string $eTag
     * @param bool $isCurrent
     */
    public function testHasCurrentStateWithNoneMatch($ifNoneMatch, $eTag, $isCurrent)
    {
        $map = [
            ['If-Match', ''],
            ['If-Unmodified-Since', ''],
            ['If-None-Match', $ifNoneMatch]
        ];

        $request = $this->getMock('Psr\Http\Message\RequestInterface');
        $request->method('getHeaderLine')->willReturnMap($map);


        $result = $this->cacheUtil->hasCurrentState($request, $eTag);
        $this->assertSame($isCurrent, $result);
    }

    /**
     * @return array
     */
    public function currentStateNoneMatches()
    {
        return [
            'current' => ['"foo"', 'bar', true],
            'not-current' => ['"foo"', 'foo', false],
            'star' => ['*', 'baz', false],
            'star-without-current' => ['*', null, true],
        ];
    }

    /**
     * @covers Micheh\Cache\CacheUtil::hasCurrentState
     */
    public function testHasCurrentStateWithNoneMatchAndSafe()
    {
        $map = [
            ['If-Match', ''],
            ['If-Unmodified-Since', ''],
            ['If-None-Match', '"foo"']
        ];

        $request = $this->getMock('Psr\Http\Message\RequestInterface');
        $request->method('getHeaderLine')->willReturnMap($map);
        $request->method('getMethod')->willReturn('GET');

        $result = $this->cacheUtil->hasCurrentState($request, 'foo');
        $this->assertTrue($result);
    }

    /**
     * @covers Micheh\Cache\CacheUtil::isNotModified
     * @covers Micheh\Cache\CacheUtil::matchesETag
     * @dataProvider notModifiedETags
     * @param string $ifNoneMatch
     * @param string $eTag
     * @param bool $notModified
     */
    public function testIsNotModifiedWithETag($ifNoneMatch, $eTag, $notModified)
    {
        $request = $this->getMock('Psr\Http\Message\RequestInterface');
        $request->expects($this->once())->method('getHeaderLine')
            ->with('If-None-Match')->willReturn($ifNoneMatch);

        $response = $this->getResponseWithHeader('ETag', $eTag);

        $result = $this->cacheUtil->isNotModified($request, $response);
        $this->assertSame($notModified, $result);
    }

    /**
     * @return array
     */
    public function notModifiedETags()
    {
        return [
            'not-modified' => ['"foo"', '"foo"', true],
            'modified' => ['"bar"', '"foo"', false],
            'not-modified-multiple' => ['"foo", "bar"', '"bar"', true],
            'modified-multiple' => ['"foo", "bar"', '"baz"', false],
            'star' => ['*', '"foo"', true],
            'star-without-current' => ['*', '', false],
            'not-modified-multiple-no-space' => ['"foo","bar"', '"bar"', true],
            'weak-client' => ['W/"foo"', '"foo"', true],
            'weak-server' => ['"foo"', 'W/"foo"', true],
            'weak-both' => ['W/"foo"', 'W/"foo"', true],
        ];
    }

    /**
     * @covers Micheh\Cache\CacheUtil::isNotModified
     * @covers Micheh\Cache\CacheUtil::matchesModified
     * @dataProvider notModifiedTimes
     * @param string $ifModifiedSince
     * @param string $lastModified
     * @param bool $notModified
     */
    public function testIsNotModifiedWithModified($ifModifiedSince, $lastModified, $notModified)
    {
        $request = $this->getMock('Psr\Http\Message\RequestInterface');

        $request->method('getHeaderLine')->willReturnMap([['If-Modified-Since', $ifModifiedSince]]);
        $request->expects($this->once())->method('getMethod')->willReturn('GET');

        $response = $this->getResponseWithHeader('Last-Modified', $lastModified);

        $result = $this->cacheUtil->isNotModified($request, $response);
        $this->assertSame($notModified, $result);
    }

    /**
     * @return array
     */
    public function notModifiedTimes()
    {
        return [
            'not-modified-equal' => ['Mon, 10 Aug 2015 18:30:12 GMT', 'Mon, 10 Aug 2015 18:30:12 GMT', true],
            'not-modified-server-earlier' => ['Mon, 10 Aug 2015 22:30:12 GMT', 'Mon, 10 Aug 2015 18:30:12 GMT', true],
            'modified-client-earlier' => ['Mon, 10 Aug 2015 11:30:12 GMT', 'Mon, 10 Aug 2015 18:30:12 GMT', false],
            'invalid-date' => ['invalid', 'Mon, 10 Aug 2015 18:30:12 GMT', false],
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
        $response = $this->getResponseWithHeader('Cache-Control', 'public');
        $response->expects($this->once())->method('getStatusCode')
            ->willReturn(200);

        $response->expects($this->once())->method('hasHeader')
            ->with('Cache-Control')->willReturn(true);

        $this->assertTrue($this->cacheUtil->isCacheable($response));
    }

    /**
     * @covers Micheh\Cache\CacheUtil::isCacheable
     */
    public function testIsCacheableWithPrivate()
    {
        $response = $this->getResponseWithHeader('Cache-Control', 'private');
        $response->expects($this->once())->method('getStatusCode')
            ->willReturn(200);

        $response->expects($this->once())->method('hasHeader')
            ->with('Cache-Control')->willReturn(true);

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
     * @covers Micheh\Cache\CacheUtil::isCacheable
     */
    public function testIsCacheableWithoutLifetime()
    {
        $response = $this->getResponse();
        $response->expects($this->once())->method('getStatusCode')
            ->willReturn(200);

        $this->assertTrue($this->cacheUtil->isCacheable($response));
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
    public function testIsFreshWithZeroAge()
    {
        $response = $this->getResponse();

        /** @var CacheUtil|\PHPUnit_Framework_MockObject_MockObject $util */
        $util = $this->getMock('Micheh\Cache\CacheUtil', ['getLifetime', 'getAge']);
        $util->expects($this->once())->method('getLifetime')
            ->with($response)->willReturn(0);

        $util->expects($this->once())->method('getAge')
            ->with($response)->willReturn(0);

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

        $this->assertNull($util->isFresh($response));
    }

    /**
     * @covers Micheh\Cache\CacheUtil::getLifetime
     */
    public function testGetLifetime()
    {
        $response = $this->getResponseWithHeader('Cache-Control', 'max-age=60, s-maxage=200');
        $response->expects($this->once())->method('hasHeader')->willReturn(true);

        $this->assertSame(200, $this->cacheUtil->getLifetime($response));
    }

    /**
     * @covers Micheh\Cache\CacheUtil::getLifetime
     */
    public function testGetLifetimeWithZero()
    {
        $response = $this->getResponseWithHeader('Cache-Control', 's-maxage=0');
        $response->expects($this->once())->method('hasHeader')->willReturn(true);

        $this->assertSame(0, $this->cacheUtil->getLifetime($response));
    }

    /**
     * @covers Micheh\Cache\CacheUtil::getLifetime
     */
    public function testGetLifetimeWithoutSharedAge()
    {
        $response = $this->getResponseWithHeader('Cache-Control', 'max-age=60, public');
        $response->expects($this->once())->method('hasHeader')->willReturn(true);

        $this->assertSame(60, $this->cacheUtil->getLifetime($response));
    }

    /**
     * @covers Micheh\Cache\CacheUtil::getLifetime
     */
    public function testGetLifetimeWithOtherCacheControlHeader()
    {
        $response = $this->getResponseWithHeader('Cache-Control', 'public');
        $response->expects($this->once())->method('hasHeader')->willReturn(true);

        $this->assertNull($this->cacheUtil->getLifetime($response));
    }

    /**
     * @covers Micheh\Cache\CacheUtil::getLifetime
     */
    public function testGetLifetimeWithExpires()
    {
        $response = $this->getResponseWithHeader('Expires', date('D, d M Y H:i:s', time() + 20));
        $this->assertSame(20, $this->cacheUtil->getLifetime($response));
    }

    /**
     * @covers Micheh\Cache\CacheUtil::getLifetime
     */
    public function testGetLifetimeWithExpiresInPast()
    {
        $response = $this->getResponseWithHeader('Expires', date('D, d M Y H:i:s', time() - 20));
        $this->assertSame(0, $this->cacheUtil->getLifetime($response));
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
        $map = [
            ['Date', date('D, d M Y H:i:s', time() - 20)],
            ['Age', ''],
        ];

        $response = $this->getResponseWithHeaders($map);

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
     * @covers Micheh\Cache\CacheUtil::getTimestampFromValue
     * @dataProvider timestamps
     */
    public function testGetTimestampFromValue($value)
    {
        $method = new ReflectionMethod('Micheh\Cache\CacheUtil', 'getTimestampFromValue');
        $method->setAccessible(true);

        $this->assertSame(
            strtotime('Mon, 10 Aug 2015 11:30:12 GMT'),
            $method->invoke($this->cacheUtil, $value)
        );
    }

    /**
     * @return array
     */
    public function timestamps()
    {
        return [
            'int' => [strtotime('Mon, 10 Aug 2015 11:30:12 GMT')],
            'datetime' => [new DateTime('Mon, 10 Aug 2015 11:30:12 GMT', new DateTimeZone('Europe/Zurich'))],
            'string' => ['Mon, 10 Aug 2015 11:30:12 GMT'],
        ];
    }

    /**
     * @covers Micheh\Cache\CacheUtil::getTimestampFromValue
     */
    public function testGetTimestampFromValueInvalidType()
    {
        $method = new ReflectionMethod('Micheh\Cache\CacheUtil', 'getTimestampFromValue');
        $method->setAccessible(true);

        $this->setExpectedException(
            'InvalidArgumentException',
            'Could not create timestamp from array.'
        );
        $method->invoke($this->cacheUtil, []);
    }

    /**
     * @covers Micheh\Cache\CacheUtil::getCacheControl
     */
    public function testGetCacheControl()
    {
        $method = new ReflectionMethod('Micheh\Cache\CacheUtil', 'getCacheControl');
        $method->setAccessible(true);

        $response = $this->getResponseWithHeader('Cache-Control', 'public');

        $cacheControl = $method->invoke($this->cacheUtil, $response);
        $this->assertInstanceOf('Micheh\Cache\Header\ResponseCacheControl', $cacheControl);
    }

    /**
     * @param string $expectedHeader
     * @param string $expectedValue
     * @return \PHPUnit_Framework_MockObject_MockObject|ResponseInterface
     */
    private function getResponseWithExpectedHeader($expectedHeader, $expectedValue)
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
     * @return \PHPUnit_Framework_MockObject_MockObject|ResponseInterface
     */
    private function getResponseWithHeader($header, $value)
    {
        return $this->getResponseWithHeaders([[$header, $value]]);
    }

    /**
     * @param array $headers
     * @return \PHPUnit_Framework_MockObject_MockObject|ResponseInterface
     */
    private function getResponseWithHeaders(array $headers)
    {
        $response = $this->getResponse();
        $response->method('getHeaderLine')->willReturnMap($headers);

        return $response;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ResponseInterface
     */
    private function getResponse()
    {
        return $this->getMock('Psr\Http\Message\ResponseInterface');
    }
}
