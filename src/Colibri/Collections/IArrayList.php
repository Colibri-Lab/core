<?php

/**
 * Интерфейс для списков
 * 
 * @author Ваган Григорян <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Collections
 * @version 1.0.0
 * 
 */

namespace Colibri\Collections;

/**
 * Интерфейс списка
 */
interface IArrayList
{
    /**
     * Возвращает знаение по индексу
     *
     * @param integer $index
     * @return mixed
     */
    public function Item($index);

    /**
     * Добавляет значение в ArrayList
     * @param mixed $value
     * @return mixed
     */
    public function Add($value);
    /**
     * Добавляет список значений в массив
     *
     * @param mixed $values
     * @return mixed
     */
    public function Append($values);

    /**
     * Удаляет значение из ArrayList-а
     *
     * @param mixed $value
     * @return boolean
     */
    public function Delete($value);
    /**
     * Удаляет значение из ArrayList-а по индексу
     *
     * @param int $index
     * @return boolean
     */
    public function DeleteAt($index);

    /**
     * Превращает в строку
     * @param string $splitter
     * @return string
     */
    public function ToString($splitter = ',');
    /**
     * Возвращает массив из значений
     *
     * @return array
     */
    public function ToArray();
    /**
     * Возвращает количество записей в массиве
     *
     * @return int
     */
    public function Count();
    /**
     * Возвращает первый пункт в списке
     * @return mixed 
     */
    public function First();
    /**
     * Возвращает последний пункт в списке
     * @return mixed 
     */
    public function Last();
}
