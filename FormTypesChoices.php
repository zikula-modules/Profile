<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ProfileModule;

class FormTypesChoices implements \ArrayAccess, \Iterator
{
    private $choices = [];

    /**
     * FormTypesChoices constructor.
     * @param array $choices
     */
    public function __construct(array $choices = [])
    {
        $this->choices = $choices;
    }

    public function offsetExists($offset)
    {
        return isset($this->choices[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->choices[$offset];
    }

    public function offsetSet($offset, $value)
    {
        if (isset($this->choices[$offset])) {
            throw new \Exception('Cannot set existing keys to new values!');
        }
        $this->choices[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        throw new \Exception('Not allowed to unset!');
    }

    function rewind() {
        return reset($this->choices);
    }

    function current() {
        return current($this->choices);
    }

    function key() {
        return key($this->choices);
    }

    function next() {
        return next($this->choices);
    }

    function valid() {
        return key($this->choices) !== null;
    }
}
