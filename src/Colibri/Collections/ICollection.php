<?php

/**
 * Интерфейс для именованных массивов
 * 
 * @author Ваган Григорян <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Collections
 * @version 1.0.0
 * 
 */

namespace Colibri\Collections;

/**
 * Интерфейс для именованных массивов
 */
interface ICollection
{

    /**
     * Проверка существования ключа в массиве
     * @param string $key
     * @return boolean
     */
    public function Exists($key);
    /**
     * Вернуть ключ по индексу
     * @param int $index
     * @return string
     */
    public function Key($index);
    /**
     * Вернуть значение по ключу
     * @param string $key
     * @return mixed
     */
    public function Item($key);
    /**
     * Вернуть значение по индексу
     * @param int $index
     * @return mixed
     */
    public function ItemAt($index);

    /**
     * Добавить ключ значение, если ключ есть, то будет произведена замена
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function Add($key, $value);
    /**
     * Удалить ключ и значение из массива
     * @param string $key
     * @return boolean
     */
    public function Delete($key);

    /**
     * В строку, с соединителями
     * @param string[] $splitters
     * @return string
     */
    public function ToString($splitters = null);
    /**
     * вернуть данные в виде обычного массива
     * @return array
     */
    public function ToArray();
}
