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
 */
class CollectionIterator implements \Iterator
{
    /**
     * Обьект коллекции
     *
     * @var ICollection
     */
    private ?\IteratorAggregate $_class;
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
    public function __construct(\IteratorAggregate $class = null)
    {
        $this->_class = $class;
    }

    /**
     * Перескопить на первую запись
     *
     */
    public function rewind(): void
    {
        $this->_current = 0;
    }

    /**
     * Вернуть текущее значение
     *
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
     */
    public function key(): string|null
    {
        return $this->_class->Key($this->_current);
    }

    /**
     * Вернуть следующее значение
     *
     */
    public function next(): void
    {
        $this->_current++;
        // if ($this->valid()) {
        //     return $this->_class->ItemAt($this->_current);
        // } else {
        //     return null;
        // }
    }

    /**
     * Проверка валидности итератора, т.е. валидно ли текущее значение
     *
     */
    public function valid(): bool
    {
        return $this->_current >= 0 && $this->_current < $this->_class->Count();
    }
}
