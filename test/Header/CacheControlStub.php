<?php

namespace MichehTest\Cache\Header;

use Micheh\Cache\Header\CacheControl;

/**
 * @author Michel Hunziker <php@michelhunziker.com>
 * @copyright Copyright (c) 2015, Michel Hunziker <php@michelhunziker.com>
 * @license http://www.opensource.org/licenses/BSD-3-Clause The BSD-3-Clause License
 */
class CacheControlStub extends CacheControl
{
    protected static $directiveMethods = [
        'custom' => 'withCustom'
    ];

    public function withCustom($value)
    {
        return $value;
    }

    public function hasDirective($name)
    {
        return parent::hasDirective($name);
    }

    public function withDirective($name, $value)
    {
        return parent::withDirective($name, $value);
    }

    public function getDirective($name)
    {
        return parent::getDirective($name);
    }
}
