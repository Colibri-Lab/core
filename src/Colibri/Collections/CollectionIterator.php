<?php

/**
 * Represents an iterator for a collection.
 * This class implements the \Iterator interface, allowing iteration over a collection.
 *
 * @author Vahan Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Collections
 * @version 1.0.0
 *
 */

namespace Colibri\Collections;

/**
 * Represents an iterator for a collection.
 * This class implements the \Iterator interface, allowing iteration over a collection.
 */
class CollectionIterator implements \Iterator
{
    /**
     * Обьект коллекции
     *
     * @var mixed
     */
    private $_class;
    /**
     * Текущая позиция
     *
     * @var mixed
     */
    private $_current = 0;

    /**
     * Конструктор, передается обьект коллекция
     *
     * @param mixed $class - коллекция
     */
    public function __construct(mixed $class = null)
    {
        $this->_class = $class;
    }

    /**
     * Перескопить на первую запись
     *
     * @testFunction testCollectionIteratorRewind
     */
    public function rewind(): mixed
    {
        $this->_current = 0;
        return $this->_class->Key($this->_current);
    }

    /**
     * Вернуть текущее значение
     *
     * @testFunction testCollectionIteratorCurrent
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
     * Вернуть ключ текущего положения
     *
     * @testFunction testCollectionIteratorKey
     */
    public function key(): mixed
    {
        return $this->_class->Key($this->_current);
    }

    /**
     * Вернуть следующее значение
     *
     * @testFunction testCollectionIteratorNext
     */
    public function next(): mixed
    {
        $this->_current++;
        if ($this->valid()) {
            return $this->_class->ItemAt($this->_current);
        } else {
            return null;
        }
    }

    /**
     * Проверка валидности итератора, т.е. валидно ли текущее значение
     *
     * @testFunction testCollectionIteratorValid
     */
    public function valid(): bool
    {
        return $this->_current >= 0 && $this->_current < $this->_class->Count();
    }
}
