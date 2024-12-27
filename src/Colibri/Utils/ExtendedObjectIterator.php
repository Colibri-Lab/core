<?php

/**
 * Utilities
 *
 * @package Colibri\Utils\Performance
 * @author Vahan P. Grigoryan
 * @copyright 2020 ColibriLab
 *
 */

namespace Colibri\Utils;

/**
 * An iterator for objects.
 */
class ExtendedObjectIterator implements \Iterator
{
    /**
     * The object collection.
     *
     * @var mixed
     */
    private $_data;

    /**
     * Keys in the data array.
     *
     * @var string[]
     */
    private $_keys;

    /**
     * Current position.
     *
     * @var mixed
     */
    private $_current = 0;

    /**
     * Constructor. Receives the object collection.
     *
     * @param array|null $data The collection
     */
    public function __construct(array $data = null)
    {
        $this->_data = (array) $data;
        $this->_keys = array_keys((array) $data);
    }

    /**
     * Rewind to the first entry.
     *
     * @return void
     */
    public function rewind(): void
    {
        $this->_current = 0;
    }

    /**
     * Return the current value.
     *
     * @return mixed
     */
    public function current(): mixed
    {
        if ($this->valid()) {
            return $this->_data[$this->_keys[$this->_current]];
        } else {
            return null;
        }
    }

    /**
     * Return the key of the current position.
     *
     * @return string
     */
    public function key(): string
    {
        if ($this->valid()) {
            return $this->_keys[$this->_current];
        }
        return null;
    }

    /**
     * Move to the next value.
     *
     * @return mixed
     */
    public function next(): void
    {
        $this->_current++;
        // if ($this->valid()) {
        //     return $this->_data[$this->_keys[$this->_current]];
        // } else {
        //     return false;
        // }
    }

    /**
     * Check the validity of the iterator, i.e., whether the current value is valid.
     *
     * @return bool
     */
    public function valid(): bool
    {
        return $this->_current >= 0 && $this->_current < count($this->_keys);
    }
}
