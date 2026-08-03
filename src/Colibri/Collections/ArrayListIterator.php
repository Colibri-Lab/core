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
 * @class
 * @implements \Iterator
 */
class ArrayListIterator implements \Iterator
{
    /**
     * Iterator data
     * @var \IteratorAggregate|null
     * @private
     */
    private ?\IteratorAggregate $_class = null;

    /**
     * Current position
     * @var int
     * @private
     */
    private int $_current = 0;

    /**
     * Constructor
     * @constructor
     * @param \IteratorAggregate|null $class The class to iterate over.
     * @return void
     * @public
     */
    public function __construct(\IteratorAggregate $class = null)
    {
        $this->_class = $class;
    }

    /**
     * Rewinds an iterator to the first item
     * @public
     * @return void
     */
    public function rewind(): void
    {
        $this->_current = 0;
    }

    /**
     * Returns item on current position
     * @suppress PHP0418
     * @public
     * @return mixed
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
     * @suppress PHP0418
     * @public
     * @return int
     */
    public function key(): int
    {
        return $this->_current;
    }

    /**
     * Returns next item and moves internal position
     * @suppress PHP0418
     * @public
     * @return void 
     */
    public function next(): void
    {
        $this->_current++;
    }

    /**
     * Check if the current position is valid
     * @suppress PHP0418
     * @public
     * @return bool
     */
    public function valid(): bool
    {
        if(method_exists($this->_class, 'Count')) {
            return $this->_current >= 0 && $this->_current < $this->_class->Count();
        }
        return true;
    }
}
