<?php

/**
 * Utils
 * 
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2020 ColibriLab
 * @package Colibri\Utils
 * 
 */

namespace Colibri\Utils;

/**
 * Итератор для обьекта
 */
class ExtendedObjectIterator implements \Iterator
{

    /**
     * Обьект коллекции
     *
     * @var mixed
     */
    private $_data;

    /**
     * Ключи в массиве данных
     * @var string[]
     */
    private $_keys;

    /**
     * Текущая позиция
     *
     * @var mixed
     */
    private $_current = 0;

    /**
     * Конструктор, передается обьект коллекция
     *
     * @param array $data - коллекция
     */
    public function __construct(array $data = null)
    {
        $this->_data = (array) $data;
        $this->_keys = array_keys((array) $data);
    }

    /**
     * Перескопить на первую запись
     * 
     * @return void
     */
    public function rewind(): void
    {
        $this->_current = 0;
    }

    /**
     * Вернуть текущее значение
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
     * Вернуть ключ текущего положения
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
     * Вернуть следующее значение
     *  
     * @return mixed
     * 
     */
    public function next(): mixed
    {
        $this->_current++;
        if ($this->valid()) {
            return $this->_data[$this->_keys[$this->_current]];
        } else {
            return false;
        }
    }

    /**
     * Проверка валидности итератора, т.е. валидно ли текущее значение
     *
     * @return bool
     */
    public function valid(): bool
    {
        return $this->_current >= 0 && $this->_current < count($this->_keys);
    }
}