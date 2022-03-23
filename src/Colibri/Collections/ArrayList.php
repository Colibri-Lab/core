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
    public function __construct($data = array())
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
    public function getIterator()
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
    public function Contains($item)
    {
        return in_array($item, $this->data, true);
    }

    /**
     * Возвращает индекс по значению
     *
     * @param mixed $item
     * @return integer
     * @testFunction testArrayListIndexOf
     */
    public function IndexOf($item)
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
    public function Item($index)
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
    public function Add($value)
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
    public function Set($index, $value)
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
    public function Append($values)
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
    public function InsertAt($value, $toIndex)
    {
        array_splice($this->data, $toIndex, 0, array($value));
    }

    /**
     * Удаляет значение
     *
     * @param mixed $value
     * @return boolean|mixed
     * @testFunction testArrayListDelete
     */
    public function Delete($value)
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
    public function DeleteAt($index)
    {
        return array_splice($this->data, $index, 1);
    }

    /**
     * Очищает список
     *
     * @return void
     * @testFunction testArrayListClear
     */
    public function Clear()
    {
        $this->data = array();
    }

    /**
     * В строку
     *
     * @param string $splitter
     * @testFunction testArrayListToString4
     */
    public function ToString($splitter = ',')
    {
        return implode($splitter, $this->data);
    }

    /**
     * Возвращает массив
     * @testFunction testArrayListToArray
     */
    public function ToArray()
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
    public function Sort($k = null, $sorttype = SORT_ASC)
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
    public function Count()
    {
        return count($this->data);
    }

    /**
     * Возвращает первый пункт в списке
     * @return mixed
     * @testFunction testArrayListFirst
     */
    public function First()
    {
        return $this->Item(0);
    }

    /**
     * Возвращает последний пункт в списке
     * @return mixed
     * @testFunction testArrayListLast
     */
    public function Last()
    {
        return $this->Item($this->Count() - 1);
    }
}
