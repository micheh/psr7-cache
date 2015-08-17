<?php

namespace Micheh\Cache\Header;

use InvalidArgumentException;

/**
 * Base class for the Cache-Control header.
 *
 * @author Michel Hunziker <php@michelhunziker.com>
 * @copyright Copyright (c) 2015, Michel Hunziker <php@michelhunziker.com>
 * @license http://www.opensource.org/licenses/BSD-3-Clause The BSD-3-Clause License
 */
abstract class CacheControl
{
    /**
     * @var array
     */
    private $directives = [];

    /**
     * @var array
     */
    private static $commonMethods = [
        'max-age' => 'withMaxAge',
        'no-cache' => 'withNoCache',
        'no-store' => 'withNoStore',
        'no-transform' => 'withNoTransform',
    ];

    /**
     * @var array Maps the directive names to the methods
     */
    protected static $directiveMethods = [];

    /**
     * Create a new Cache-Control object from a header string.
     *
     * @param string $string
     * @return static
     */
    public static function fromString($string)
    {
        $cacheControl = new static();

        $parts = explode(',', $string);
        foreach ($parts as $part) {
            $index = strpos($part, '=');
            if ($index !== false) {
                $directive = substr($part, 0, $index);
                $value = trim(substr($part, $index + 1));
            } else {
                $directive = $part;
                $value = true;
            }

            $directive = trim($directive);
            $method = self::getMethod($directive);

            if ($method === null && $value === true) {
                // Ignore unknown flag
                continue;
            }

            $cacheControl = $method
                ? $cacheControl->$method($value)
                : $cacheControl->withExtension($directive, $value);
        }

        return $cacheControl;
    }

    /**
     * Set how many seconds to cache.
     *
     * @param int $seconds
     * @return static
     */
    public function withMaxAge($seconds)
    {
        return $this->withDirective('max-age', (int) $seconds);
    }

    /**
     * @return int|null
     */
    public function getMaxAge()
    {
        return $this->getDirective('max-age');
    }

    /**
     * Set whether a representation should be cached.
     *
     * @param bool $flag
     * @return static
     */
    public function withNoCache($flag = true)
    {
        return $this->withFlag('no-cache', $flag);
    }

    /**
     * @return bool
     */
    public function hasNoCache()
    {
        return $this->hasFlag('no-cache');
    }

    /**
     * Set whether a representation should be stored.
     *
     * @param bool $flag
     * @return static
     */
    public function withNoStore($flag = true)
    {
        return $this->withFlag('no-store', $flag);
    }

    /**
     * @return bool
     */
    public function hasNoStore()
    {
        return $this->hasFlag('no-store');
    }

    /**
     * Set whether the payload can be transformed.
     *
     * @param bool $flag
     * @return static
     */
    public function withNoTransform($flag = true)
    {
        return $this->withFlag('no-transform', $flag);
    }

    /**
     * @return bool
     */
    public function hasNoTransform()
    {
        return $this->hasFlag('no-transform');
    }

    /**
     * Add a custom extension directive to the Cache-Control.
     *
     * @param string $name Name of the directive
     * @param string $value Value of the directive as a string
     * @return static
     * @throws InvalidArgumentException If the name or the value of the directive is not a string
     */
    public function withExtension($name, $value)
    {
        if (!is_string($name) || !is_string($value)) {
            throw new InvalidArgumentException('Name and value of the extension have to be a string.');
        }

        return $this->withDirective($name, trim($value, '" '));
    }

    /**
     * @param string $name
     * @return string|null
     */
    public function getExtension($name)
    {
        return $this->getDirective($name);
    }

    /**
     * @return string Header string, which can added to the response.
     */
    public function __toString()
    {
        $parts = [];
        foreach ($this->directives as $directive => $value) {
            if ($value === true) {
                $parts[] = $directive;
                continue;
            }

            if (is_string($value)) {
                $value = '"' . $value . '"';
            }

            $parts[] = $directive . '=' . $value;
        }

        return implode(', ', $parts);
    }

    /**
     * Set a flag directive with the provided name. If the flag is `false` the directive is removed.
     *
     * @param string $name Name of the directive
     * @param bool $flag Whether the directive should appear in the header
     * @return static
     */
    protected function withFlag($name, $flag)
    {
        $clone = clone($this);

        if ($flag) {
            $clone->directives[$name] = true;
            return $clone;
        }

        if (isset($clone->directives[$name])) {
            unset($clone->directives[$name]);
        }

        return $clone;
    }

    /**
     * Returns true if the Cache-Control has a flag directive and false otherwise.
     *
     * @param string $name Name of the directive
     * @return bool
     */
    protected function hasFlag($name)
    {
        return isset($this->directives[$name]);
    }

    /**
     * Set a directive with the provided name and value.
     *
     * @param string $name Name of the directive
     * @param string|int|null $value Value of the directive
     * @return static
     */
    protected function withDirective($name, $value)
    {
        $clone = clone($this);

        if ($value !== null) {
            $clone->directives[$name] = $value;
            return $clone;
        }

        if (array_key_exists($name, $clone->directives)) {
            unset($clone->directives[$name]);
        }

        return $clone;
    }

    /**
     * Returns the directive value if available, or `null` if not available.
     *
     * @param string $name Name of the directive
     * @return string|int|null
     */
    protected function getDirective($name)
    {
        if (array_key_exists($name, $this->directives)) {
            return $this->directives[$name];
        }

        return null;
    }

    /**
     * Returns the method name to set the provided directive. If the directive cannot be set, the
     * method returns `null`.
     *
     * @param string $directive Name of the directive
     * @return string|null
     */
    private static function getMethod($directive)
    {
        if (isset(static::$directiveMethods[$directive])) {
            return static::$directiveMethods[$directive];
        }

        if (isset(self::$commonMethods[$directive])) {
            return self::$commonMethods[$directive];
        }

        return null;
    }
}
