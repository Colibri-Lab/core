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
    public function Item(int $index) : mixed;

    /**
     * Добавляет значение в ArrayList
     * @param mixed $value
     * @return mixed
     */
    public function Add(mixed $value) : mixed;
    /**
     * Добавляет список значений в массив
     *
     * @param mixed $values
     * @return mixed
     */
    public function Append(mixed $values) : void;

    /**
     * Удаляет значение из ArrayList-а
     *
     * @param mixed $value
     * @return boolean
     */
    public function Delete(mixed $value) : bool;
    
    /**
     * Удаляет значение из ArrayList-а по индексу
     *
     * @param int $index
     * @return array
     */
    public function DeleteAt(int $index) : array;

    /**
     * Превращает в строку
     * @param string $splitter
     * @return string
     */
    public function ToString(string $splitter = ',') : string;
    /**
     * Возвращает массив из значений
     *
     * @return array
     */
    public function ToArray() : array;
    /**
     * Возвращает количество записей в массиве
     *
     * @return int
     */
    public function Count() : int;
    /**
     * Возвращает первый пункт в списке
     * @return mixed 
     */
    public function First() : mixed;
    /**
     * Возвращает последний пункт в списке
     * @return mixed 
     */
    public function Last() : mixed;
}
