<?php

/**
 * Список
 *
 * @author Ваган Григорян <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Collections
 * @version 1.0.0
 *
 */

namespace Colibri\Collections;

/**
 * Базовый класс списка, реализует стандартный функционал
 * @testFunction testArrayList
 */
class ArrayList implements IArrayList, \IteratorAggregate
{

    /**
     * Данные
     *
     * @var mixed
     */
    protected $data = null;

    /**
     * Конструктор
     * Создаем ArrayList из массива или объекта или из другого ArrayList-а
     *
     * @param mixed $data
     * @testFunction testArrayList
     */
    public function __construct(mixed $data = array())
    {
        if (is_array($data)) {
            $this->data = $data;
        } elseif (is_object($data) && $data instanceof IArrayList) {
            $this->data = $data->ToArray();
        }

        if (is_null($this->data)) {
            $this->data = array();
        }
    }

    /**
     * Получить иттератор
     *
     * @return ArrayListIterator
     * @testFunction testArrayListGetIterator
     * 
     */
    public function getIterator() : ArrayListIterator
    {
        return new ArrayListIterator($this);
    }

    /**
     * Проверка наличия значения в списке
     *
     * @param mixed $item
     * @return boolean
     * @testFunction testArrayListContains
     */
    public function Contains(mixed $item) : bool
    {
        return in_array($item, $this->data, true);
    }

    /**
     * Возвращает индекс по значению
     *
     * @param mixed $item
     * @return int
     * @testFunction testArrayListIndexOf
     */
    public function IndexOf(mixed $item) : int
    {
        return array_search($item, $this->data, true);
    }

    /**
     * Возвращает значение по идексу
     *
     * @param integer $index
     * @return mixed
     * @testFunction testArrayListItem
     */
    public function Item(int $index) : mixed
    {
        if (!isset($this->data[$index])) {
            return null;
        }
        return $this->data[$index];
    }

    /**
     * Добавляет значение с список
     *
     * @param mixed $value
     * @return mixed
     * @testFunction testArrayListAdd
     */
    public function Add(mixed $value) : mixed
    {
        $this->data[] = $value;
        return $value;
    }

    /**
     * Устанавливает значение по указанному индексу
     *
     * @param integer $index
     * @param mixed $value
     * @return mixed
     * @testFunction testArrayListSet
     */
    public function Set(int $index, mixed $value) : mixed
    {
        $this->data[$index] = $value;
        return $value;
    }

    /**
     * Добавляет значения
     *
     * @param mixed $values
     * @return void
     * @testFunction testArrayListAppend
     */
    public function Append(mixed $values) : void
    {
        if ($values instanceof IArrayList) {
            $values = $values->ToArray();
        }

        $this->data = array_merge($this->data, $values);
    }

    /**
     * Внедряет значение в список после индекса
     *
     * @param mixed $value
     * @param integer $toIndex
     * @return void
     * @testFunction testArrayListInsertAt
     */
    public function InsertAt(mixed $value, int $toIndex) : void
    {
        array_splice($this->data, $toIndex, 0, array($value));
    }

    /**
     * Удаляет значение
     *
     * @param mixed $value
     * @return boolean
     * @testFunction testArrayListDelete
     */
    public function Delete(mixed $value) : bool
    {
        $indices = array_search($value, $this->data, true);
        if ($indices > -1) {
            array_splice($this->data, $indices, 1);
            return true;
        }
        return false;
    }

    /**
     * Удаляет значение по индексу
     *
     * @param integer $index
     * @return mixed
     * @testFunction testArrayListDeleteAt
     */
    public function DeleteAt(int $index) : array
    {
        return array_splice($this->data, $index, 1);
    }

    /**
     * Очищает список
     *
     * @return void
     * @testFunction testArrayListClear
     */
    public function Clear() : void
    {
        $this->data = array();
    }

    /**
     * В строку
     *
     * @param string $splitter
     * @testFunction testArrayListToString4
     */
    public function ToString(string $splitter = ',') : string
    {
        return implode($splitter, $this->data);
    }

    /**
     * Возвращает массив
     * @testFunction testArrayListToArray
     */
    public function ToArray() : array
    {
        return $this->data;
    }

    /**
     * Сортирует данные в массиве
     *
     * @param string|null $k - ключ сортировки
     * @param int $sorttype - порядок сортировки
     * @testFunction testArrayListSort
     */
    public function Sort(string $k = null, int $sorttype = SORT_ASC) : void
    {
        $rows = array();
        $i = 0;
        foreach ($this->data as $index => $row) {
            if(is_object($row)) {
                $key = $row->$k; 
            }
            else if(is_array($row)) {
                $key = $row[$k];
            }
            else {
                $key = $index;
            }

            if(isset($rows[$key])) {
                $key = $key.($i++);
            }
            $rows[$key] = $row;
        }

        if($sorttype == SORT_ASC) {
            ksort($rows);
        }
        else {
            krsort($rows);
        }
        $this->data = array_values($rows);
    }

    /**
     * Возвращает количество записей в массиве
     *
     * @return int
     * @testFunction testArrayListCount
     */
    public function Count() : int
    {
        return count($this->data);
    }

    /**
     * Возвращает первый пункт в списке
     * @return mixed
     * @testFunction testArrayListFirst
     */
    public function First() : mixed
    {
        return $this->Item(0);
    }

    /**
     * Возвращает последний пункт в списке
     * @return mixed
     * @testFunction testArrayListLast
     */
    public function Last() : mixed
    {
        return $this->Item($this->Count() - 1);
    }
}
