<?php
/**
 * PSR-7 Cache Helpers
 *
 * @copyright Copyright (c) 2016, Michel Hunziker <php@michelhunziker.com>
 * @license http://www.opensource.org/licenses/BSD-3-Clause The BSD-3-Clause License
 */

namespace MichehTest\Cache\Header;

use Micheh\Cache\Header\CacheControl;

class CacheControlTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CacheControl
     */
    protected $cacheControl;

    /**
     * @var string
     */
    protected $controlClass = 'Micheh\Cache\Header\CacheControl';

    protected function setUp()
    {
        $this->cacheControl = new $this->controlClass();
    }

    /**
     * @param string $value
     */
    protected function assertReturn($value)
    {
        $this->assertEquals('phpunit', $value, 'Method did not return the value');
    }

    /**
     * @param string $name
     * @param string|int $value
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getControlWithDirective($name, $value)
    {
        $control = $this->getMock($this->controlClass, ['withDirective']);
        $control->expects($this->once())->method('withDirective')
            ->with($name, $value)->willReturn('phpunit');

        return $control;
    }

    /**
     * @param string $name
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getControlWithGetDirective($name)
    {
        $control = $this->getMock($this->controlClass, ['getDirective']);
        $control->expects($this->once())->method('getDirective')
            ->with($name)->willReturn('phpunit');

        return $control;
    }

    /**
     * @param string $name
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getControlWithHasFlag($name)
    {
        $control = $this->getMock($this->controlClass, ['hasDirective']);
        $control->expects($this->once())->method('hasDirective')
            ->with($name)->willReturn('phpunit');

        return $control;
    }
}
