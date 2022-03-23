<?php

/**
 * Итератор списка
 * 
 * @author Ваган Григорян <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Collections
 * @version 1.0.0
 * 
 */

namespace Colibri\Collections;

use IteratorAggregate;

/**
 * Итератор списка
 * @testFunction testArrayListIterator
 */
class ArrayListIterator implements \Iterator
{

    /**
     * Данные итератора
     * @var IArrayList
     */
    private $_class;

    /**
     * Текущая позиция
     * @var int
     */
    private $_current = 0;

    /**
     * Создает итератор для ArrayList-а
     *
     * @param IteratorAggregate $class
     */
    public function __construct($class = null)
    {
        $this->_class = $class;
    }

    /**
     * Перескакивает на первое значение и возвращает позицию
     *
     * @return mixed
     * @testFunction testArrayListIteratorRewind
     */
    public function rewind()
    {
        $this->_current = 0;
        return $this->_current;
    }

    /**
     * Возвращает текущую позицию
     *
     * @return mixed
     * @testFunction testArrayListIteratorCurrent
     */
    public function current()
    {
        if ($this->valid()) {
            return $this->_class->Item($this->_current);
        } else {
            return false;
        }
    }

    /**
     * Возвращает ключ текущей позиции
     *
     * @return int
     * @testFunction testArrayListIteratorKey
     */
    public function key()
    {
        return $this->_current;
    }

    /**
     * Переходит дальше и возвращает значение
     *
     * @return mixed
     * @testFunction testArrayListIteratorNext
     */
    public function next()
    {
        $this->_current++;
        if ($this->valid()) {
            return $this->_class->Item($this->_current);
        } else {
            return null;
        }
    }

    /**
     * Проверяет валидна ли текущая позиция
     *
     * @return bool
     * @testFunction testArrayListIteratorValid
     */
    public function valid()
    {
        return $this->_current >= 0 && $this->_current < $this->_class->Count();
    }
}
