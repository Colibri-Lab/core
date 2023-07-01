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
     */
    private ?IteratorAggregate $_class = null;

    /**
     * Текущая позиция
     */
    private int $_current = 0;

    /**
     * Создает итератор для ArrayList-а
     */
    public function __construct(IteratorAggregate $class = null)
    {
        $this->_class = $class;
    }

    /**
     * Перескакивает на первое значение и возвращает позицию
     * @testFunction testArrayListIteratorRewind
     */
    public function rewind(): mixed
    {
        $this->_current = 0;
        return $this->_current;
    }

    /**
     * Возвращает текущую позицию
     * @testFunction testArrayListIteratorCurrent
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
     * Возвращает ключ текущей позиции
     * @testFunction testArrayListIteratorKey
     */
    public function key(): int
    {
        return $this->_current;
    }

    /**
     * Переходит дальше и возвращает значение
     * @suppress PHP0418
     * @testFunction testArrayListIteratorNext
     */
    public function next(): mixed
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
     * @suppress PHP0418
     * @testFunction testArrayListIteratorValid
     */
    public function valid(): bool
    {
        return $this->_current >= 0 && $this->_current < $this->_class->Count();
    }
}