<?php

/**
 * Simple array list
 *
 * @author Vahan Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Collections
 * @version 1.0.0
 *
 */

namespace Colibri\Collections;

use ArrayAccess;
use Countable;
use JsonSerializable;
use Colibri\Utils\Debug;

/**
 * Base class for array lists
 */
class ArrayList implements IArrayList, \IteratorAggregate, JsonSerializable, ArrayAccess, Countable
{

    /**
     * JSON schema
     */
    public const JsonSchema = [
        'type' => 'array',
        'items' => [
            'type' => 'object',
            'patternProperties' => [
                '.*' => [
                    'type' => ['number', 'string', 'boolean', 'object', 'array', 'null']
                ]
            ]
        ]
    ];

    /**
     * Data
     *
     * @var mixed
     */
    protected $data = null;

    /**
     * Constructor
     * Creates an array list from array|object or else
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
     * Gets an interator     * 
     */
    public function getIterator(): ArrayListIterator
    {
        return new ArrayListIterator($this);
    }

    /**
     * Check when item contains in internal array
     */
    public function Contains(mixed $item): bool
    {
        return in_array($item, $this->data, true);
    }

    /**
     * Returns index by item
     * @testFunction testArrayListIndexOf
     */
    public function IndexOf(mixed $item): int
    {
        return array_search($item, $this->data, true);
    }

    /**
     * Returns item by index
     */
    public function Item(int $index): mixed
    {
        if (!isset($this->data[$index])) {
            return null;
        }
        return $this->data[$index];
    }

    /**
     * Adds item to array list
     * @testFunction testArrayListAdd
     */
    public function Add(mixed $value): mixed
    {
        $this->data[] = $value;
        return $value;
    }

    /**
     * Sets an item to specified place in array list
     */
    public function Set(int $index, mixed $value): mixed
    {
        $this->data[$index] = $value;
        return $value;
    }

    /**
     * Appends an items to array list
     */
    public function Append(mixed $values): void
    {
        if ($values instanceof IArrayList) {
            $values = $values->ToArray();
        }

        $this->data = array_merge($this->data, $values);
    }

    /**
     * Inserts an item to specified index
     */
    public function InsertAt(mixed $value, int $toIndex): void
    {
        array_splice($this->data, $toIndex, 0, array($value));
    }

    /**
     * Deletes an item
     */
    public function Delete(mixed $value): bool
    {
        $indices = array_search($value, $this->data, true);
        if ($indices > -1) {
            array_splice($this->data, $indices, 1);
            return true;
        }
        return false;
    }

    /**
     * Deletes an item by index
     */
    public function DeleteAt(int $index): array
    {
        return array_splice($this->data, $index, 1);
    }

    /**
     * Clears an array list
     */
    public function Clear(): void
    {
        $this->data = array();
    }

    /**
     * Returns string representation of array list
     */
    public function ToString(string $splitter = ','): string
    {
        return implode($splitter, $this->data);
    }

    /**
     * Returns internal array
     */
    public function ToArray(): array
    {
        return $this->data;
    }

    /**
     * Sorts an array using sort function
     */
    public function SortByClosure(\Closure $closure): self
    {
        $array = $this->ToArray();
        usort($array, $closure);
        $this->Clear();
        $this->Append($array);
        return $this;
    }
    
    /**
     * Sorts an array list by using internal object field by key
     */
    public function Sort(string $k = null, int $sorttype = SORT_ASC): void
    {
        $rows = array();
        $i = 0;
        foreach ($this->data as $index => $row) {
            if (is_object($row)) {
                $key = $row->$k;
            } elseif (is_array($row)) {
                $key = $row[$k];
            } else {
                $key = $index;
            }

            if (isset($rows[$key])) {
                $key = $key . ($i++);
            }
            $rows[$key] = $row;
        }

        if ($sorttype == SORT_ASC) {
            ksort($rows);
        } else {
            krsort($rows);
        }
        $this->data = array_values($rows);
    }

    /**
     * Returns internal array count
     */
    public function Count(): int
    {
        return count($this->data);
    }

    /**
     * Returns first item of array list
     */
    public function First(): mixed
    {
        return $this->Item(0);
    }

    /**
     * Returns last item of array list
     */
    public function Last(): mixed
    {
        return $this->Item($this->Count() - 1);
    }

    /**
     * Return internal array for json conversion
     */
    public function jsonSerialize(): array
    {
        return $this->ToArray();
    }

    /**
     * Filters an array list by closure
     */
    public function Filter(\Closure $closure): ArrayList
    {
        $newList = new ArrayList();
        foreach ($this as $value) {
            if ($closure($value) === true) {
                $newList->Add($value);
            }
        }
        return $newList;
    }

    /**
     * Finds an item by closure
     */
    public function Find(\Closure $closure): mixed
    {

        $filtered = $this->Filter($closure);
        if(empty($filtered)) {
            return null;
        }

        return $filtered[0];
    }

    /**
     * Executes an closure for every item and returns new array list
     */
    public function Map(\Closure $closure): ArrayList
    {
        $newList = new ArrayList();
        foreach ($this as $value) {
            $newList->Add($closure($value));
        }
        return $newList;
    }

    /**
     * Sets an item by index (used for ArrayAccess)
     * @param int $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_null($offset)) {
            $this->Add($value);
        } else {
            $this->Set($offset, $value);
        }
    }

    /**
     * Checks if offset exists in array list (used for ArrayAccess)
     * @param int $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return $offset < $this->Count();
    }

    /**
     * Deletes an item from array list by index (used for ArrayAccess)
     * @param int $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->DeleteAt($offset);
    }

    /**
     * Возвращает значение по индексу
     *
     * @param int $offset
     * @return mixed
     * @testFunction testDataTableOffsetGet (used for ArrayAccess)
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->Item($offset);
    }

}