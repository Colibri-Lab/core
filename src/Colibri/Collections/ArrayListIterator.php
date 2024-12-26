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
 * Array list iterator
 */
class ArrayListIterator implements \Iterator
{

    /**
     * Iterator data
     */
    private ?\IteratorAggregate $_class = null;

    /**
     * Current position
     */
    private int $_current = 0;

    /**
     * Constructor
     */
    public function __construct(\IteratorAggregate $class = null)
    {
        $this->_class = $class;
    }

    /**
     * Rewinds an iterator to the first item
     */
    public function rewind(): void
    {
        $this->_current = 0;
    }

    /**
     * Returns item on current position
     * @suppress PHP0418
     */
    public function current(): mixed
    {
        if ($this->valid() && method_exists($this->_class, 'Item')) {
            return $this->_class->Item($this->_current);
        } else {
            return false;
        }
    }

    /**
     * Returns key (index) on current position
     */
    public function key(): int
    {
        return $this->_current;
    }

    /**
     * Returns next item and moves internal position
     * @suppress PHP0418
     */
    public function next(): void
    {
        $this->_current++;
        // if ($this->valid()) {
        //     return $this->_class->Item($this->_current);
        // } else {
        //     return null;
        // }
    }

    /**
     * Check if the current position is valid
     * @suppress PHP0418
     */
    public function valid(): bool
    {
        return $this->_current >= 0 && $this->_current < $this->_class->Count();
    }
}