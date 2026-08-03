<?php

/**
 * Collections
 *
 * @author Vahan Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Collections
 */

namespace Colibri\Collections;

/**
 * Represents an iterator for a collection.
 * This class implements the \Iterator interface, allowing iteration over a collection.
 * @class
 * @implements \Iterator
 */
class CollectionIterator implements \Iterator
{
    /**
     * Collection object
     *
     * @var ICollection
     * @private 
     */
    private ?\IteratorAggregate $_class;
    /**
     * Current position
     *
     * @var mixed
     * @private
     */
    private $_current = 0;

    /**
     * Constructor, accepts a collection object
     *
     * @param mixed $class - collection
     * @return void
     * @public
     * @constructor
     */
    public function __construct(\IteratorAggregate $class = null)
    {
        $this->_class = $class;
    }

    /**
     * Rewind to the first record
     * @return void
     * @public
     */
    public function rewind(): void
    {
        $this->_current = 0;
    }

    /**
     * Return the current value
     * @return mixed The current element or null if not valid.
     * @public
     */
    public function current(): mixed
    {
        if ($this->valid()) {
            return $this->_class->ItemAt($this->_current);
        } else {
            return null;
        }
    }

    /**
     * Return the key of the current position
     * @return mixed The key of the current element or null if not valid.
     * @public
     */
    public function key(): string|null
    {
        return $this->_class->Key($this->_current);
    }

    /**
     * Move to the next value
     * @return void
     * @public
     */
    public function next(): void
    {
        $this->_current++;
    }

    /**
     * Validate the iterator, i.e. whether the current value is valid
     * @return bool Returns `true` if the current position is valid, `false` otherwise.
     * @public
     */
    public function valid(): bool
    {
        return $this->_current >= 0 && $this->_current < $this->_class->Count();
    }
}
