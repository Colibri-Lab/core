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
    public function Exists(string $key): bool;
    /**
     * Вернуть ключ по индексу
     * @param int $index
     * @return string
     */
    public function Key(int $index): ?string;
    /**
     * Вернуть значение по ключу
     * @param string $key
     * @return mixed
     */
    public function Item(string $key): mixed;
    /**
     * Вернуть значение по индексу
     * @param int $index
     * @return mixed
     */
    public function ItemAt(int $index): mixed;

    /**
     * Добавить ключ значение, если ключ есть, то будет произведена замена
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function Add(string $key, mixed $value): mixed;
    /**
     * Удалить ключ и значение из массива
     * @param string $key
     * @return boolean
     */
    public function Delete(string $key): bool;

    /**
     * В строку, с соединителями
     * @param string[] $splitters
     * @return string
     */
    public function ToString(array $splitters = null): string;
    /**
     * вернуть данные в виде обычного массива
     * @return array
     */
    public function ToArray(): array;
}
