<?php

/**
 * Коллекция
 * 
 * @author Ваган Григорян <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Collections
 * @version 1.0.0
 * 
 */

namespace Colibri\Collections;

use ArrayAccess;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use JsonSerializable;

/**
 * Базовый класс коллекций
 * @testFunction testCollection
 */
class Collection implements ICollection, IteratorAggregate, JsonSerializable, ArrayAccess, Countable
{

    /**
     * Данные коллекции
     */
    protected mixed $data = null;

    /**
     * Конструктор, передается массив или обькет, или другая
     * коллекция для инициализации
     * Инициализация с помощью: array, stdClass, и любого другого ICollection
     */
    public function __construct(mixed $data = array())
    {
        if (is_array($data)) {
            $this->data = $data;
        } elseif (is_object($data)) {
            $this->data = $data instanceof ICollection ? $data->ToArray() : (array) $data;
        }

        if (is_null($this->data)) {
            $this->data = array();
        }

        $this->data = array_change_key_case($this->data, CASE_LOWER);
    }

    /**
     * Проверяет наличие ключа
     * @testFunction testCollectionExists
     */
    public function Exists(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Проверяет содержит ли коллекция значение
     * @testFunction testCollectionContains
     */
    public function Contains(mixed $item): bool
    {
        return in_array($item, $this->data, true);
    }

    /**
     * Находит значение и возвращает индекс
     * @testFunction testCollectionIndexOf
     */
    public function IndexOf(mixed $item): mixed
    {
        $return = array_search($item, array_values($this->data), true);
        if ($return === false) {
            return null;
        }
        return $return;
    }

    /**
     * Возвращает ключ по индексу
     * @testFunction testCollectionKey
     */
    public function Key(int $index): ?string
    {
        if ($index >= $this->Count() || $index < 0) {
            return null;
        }

        $keys = array_keys($this->data);
        if (!empty($keys)) {
            return $keys[$index];
        }

        return null;
    }

    /**
     * Возвращает значение по ключу
     * @testFunction testCollectionItem
     */
    public function Item(string $key): mixed
    {
        if ($this->Exists($key)) {
            return $this->data[$key];
        }
        return null;
    }

    /**
     * Возвращает знаение по индексу
     * @testFunction testCollectionItemAt
     */
    public function ItemAt(int $index): mixed
    {
        $key = $this->Key($index);
        if (!$key) {
            return null;
        }
        return $this->data[$key];
    }

    /**
     * Возвращает итератор
     * @testFunction testCollectionGetIterator
     */
    public function getIterator(): CollectionIterator
    {
        return new CollectionIterator($this);
    }

    /**
     * Добавляет ключ значение в коллекцию, если ключ существует
     * будет произведена замена
     * @testFunction testCollectionAdd
     */
    public function Add(string $key, mixed $value): mixed
    {
        $this->data[strtolower($key)] = $value;
        return $value;
    }

    /**
     * Добавляет значения из другой коллекции, массива или обьекта
     * Для удаления необходимо передать свойство со значением null
     * @testFunction testCollectionAppend
     */
    public function Append(mixed $from): void
    {
        foreach ($from as $key => $value) {
            if (is_null($value)) {
                $this->Delete($key);
            } else {
                $this->Add($key, $value);
            }
        }
    }

    /**
     * Добавляет значение в указанное место в коллекцию
     * @testFunction testCollectionInsert
     */
    public function Insert(mixed $index, mixed $key, mixed $value): mixed
    {
        $before = array_splice($this->data, 0, $index);
        $this->data = array_merge(
            $before,
            array($key => $value),
            $this->data
        );
        return $value;
    }

    /**
     * Удаляет значение по ключу
     * @testFunction testCollectionDelete
     */
    public function Delete(string $key): bool
    {
        $key = strtolower($key);
        if (array_key_exists($key, $this->data)) {
            unset($this->data[$key]);
            return true;
        }
        return false;
    }

    /**
     * Удаляет значение по индексу
     * @testFunction testCollectionDeleteAt
     */
    public function DeleteAt(int $index): bool
    {
        $key = $this->Key($index);
        if ($key !== null) {
            $this->Delete($key);
            return true;
        }
        return false;
    }

    /**
     * Очищает коллекцию
     * @testFunction testCollectionClear
     */
    public function Clear(): void
    {
        $this->data = array();
    }

    /**
     * Превращает в строку
     *
     * @param string[] $splitters
     * @testFunction testCollectionToString
     */
    public function ToString(array $splitters = null, mixed $mapFunction = null): string
    {
        $ret = [];
        foreach ($this->data as $k => $v) {
            $ret[] =
                $k .
                ($splitters && isset($splitters[0]) ? $splitters[0] : '') .
                ($mapFunction && \is_callable($mapFunction) ? $mapFunction($k, $v) : $v);
        }
        return implode(isset($splitters[1]) ? $splitters[1] : '', $ret);
    }

    /**
     * Парсит строку и сохраняет в коллекцию
     *
     * @param string $string
     * @param string[] $splitters
     * @return Collection
     */
    /**
     * @testFunction testCollectionFromString
     */
    public static function FromString(string $string, array $splitters = null): Collection
    {
        if (!$splitters) {
            return new Collection();
        }
        $ret = array();
        $parts = explode(isset($splitters[1]) ? $splitters[1] : '&', $string);
        foreach ($parts as $part) {
            $part = explode(isset($splitters[0]) ? $splitters[0] : '=', $part);
            $ret[$part[0]] = $part[1];
        }
        return new Collection($ret);
    }

    /**
     * Возвращает данные в виде массива
     *
     * @return array
     * @testFunction testCollectionToArray
     */
    public function ToArray(): array
    {
        return $this->data;
    }

    /**
     * Количество значений в коллекции
     *
     * @return int
     * @testFunction testCollectionCount
     */
    public function Count(): int
    {
        return count($this->data);
    }

    /**
     * Возвращает первое значение
     *
     * @return mixed
     * @testFunction testCollectionFirst
     */
    public function First(): mixed
    {
        return $this->ItemAt(0);
    }

    /**
     * Возвращает последнее значение
     *
     * @return mixed
     * @testFunction testCollectionLast
     */
    public function Last(): mixed
    {
        return $this->ItemAt($this->Count() - 1);
    }

    /**
     * Магическая функция
     *
     * @param string $property
     * @return mixed
     */
    public function __get(string $property): mixed
    {
        return $this->Item(strtolower($property));
    }

    /**
     * Магическая функция
     *
     * @param string $property
     * @param mixed $value
     * @return void
     */
    public function __set(string $key, mixed $value): void
    {
        $this->Add($key, $value);
    }

    public function Filter(\Closure $closure): Collection
    {
        $newColection = new Collection();
        foreach ($this as $key => $value) {
            if ($closure($key, $value) === true) {
                $newColection->Add($key, $value);
            }
        }
        return $newColection;
    }

    public function Extract($page, $pagesize): Collection
    {
        $start = ($page - 1) * $pagesize;
        $end = min($start + $pagesize, $this->Count());

        $newCollection = new Collection();
        for ($i = $start; $i < $end; $i++) {
            $newCollection->Add($this->Key($i), $this->ItemAt($i));
        }
        return $newCollection;
    }

    public function jsonSerialize(): array
    {
        return $this->ToArray();
    }

    /**
     * Устанавливает значение по индексу
     * @param string $offset
     * @param mixed $value
     * @return void
     * @testFunction testDataTableOffsetSet
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_string($offset)) {
            $this->Add($offset, $value);
        } else {
            throw new InvalidArgumentException('Invalid offset');
        }
    }

    /**
     * Проверяет есть ли данные по индексу
     * @param string|int $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        if (is_string($offset)) {
            return $this->Exists($offset);
        } else {
            return $offset < $this->Count();
        }
    }

    /**
     * удаляет данные по индексу
     * @param string|int $offset
     * @return void
     * @testFunction testDataTableOffsetUnset
     */
    public function offsetUnset(mixed $offset): void
    {
        if (is_string($offset)) {
            $this->Delete($offset);
        } else {
            $this->DeleteAt($offset);
        }
    }

    /**
     * Возвращает значение по индексу
     *
     * @param int $offset
     * @return mixed
     * @testFunction testDataTableOffsetGet
     */
    public function offsetGet(mixed $offset): mixed
    {
        if (is_string($offset)) {
            return $this->Item($offset);
        } else {
            return $this->ItemAt($offset);
        }
    }


}