<?php

/**
 * Итератор для коллекции, чтобы можно было использовать в foreach
 * 
 * @author Ваган Григорян <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Collections
 * @version 1.0.0
 * 
 */

namespace Colibri\Collections;

/**
 * Итератор для коллекции, чтобы можно было использовать в foreach
 * @testFunction testCollectionIterator
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