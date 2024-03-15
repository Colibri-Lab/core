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
     * 
     * ```
     * $array1 = new ArrayList([1, 2, 3]);
     * or copy existing array
     * $array2 = new ArrayList($array1);
     * ```
     * 
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
     * Gets an interator     
     * 
     * ```
     * For example
     * 
     * $array = new ArrayList();
     * foreach($array as $item) { ... }
     * 
     * or 
     * 
     * foreach($array->getIterator() as $item) { ... }
     * 
     * ```
     */
    public function getIterator(): ArrayListIterator
    {
        return new ArrayListIterator($this);
    }

    /**
     * Check when item contains in internal array
     * 
     * ```
     * $array = new ArrayList([1,2,3]);
     * $array->Contains(3) returns true
     * $array->Contains(5) returns false
     * ```
     */
    public function Contains(mixed $item): bool
    {
        return in_array($item, $this->data, true);
    }

    /**
     * Returns index by item
     * 
     * ```
     * $array = new ArrayList([1,2,3]);
     * $array->IndexOf(3) returns 2
     * $array->IndexOf(5) returns -1
     * ```
     * 
     */
    public function IndexOf(mixed $item): int
    {
        return array_search($item, $this->data, true);
    }

    /**
     * Returns item by index
     * 
     * ```
     * $array = new ArrayList([1,2,3]);
     * $array->Item(2) returns 3
     * $array->Item(5) returns null
     * ```
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
     *
     * ```
     * $array = new ArrayList([1,2,3]);
     * $array->Add(5) and ArrayList then will contain [1,2,3,5]
     * ```
     * 
     */
    public function Add(mixed $value): mixed
    {
        $this->data[] = $value;
        return $value;
    }

    /**
     * Sets an item to specified place in array list
     * 
     * ```
     * $array = new ArrayList([1,2,3]);
     * $array->Set(1, 6) and ArrayList then will contain [1,6,3]
     * ```
     */
    public function Set(int $index, mixed $value): mixed
    {
        $this->data[$index] = $value;
        return $value;
    }

    /**
     * Appends an items to array list
     * 
     * ```
     * $array = new ArrayList([1,2,3]);
     * $array->Append([5,6,7]) and ArrayList then will contain [1,2,3,5,6,7]
     * ```
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
     * 
     * ```
     * $array = new ArrayList([1,2,3]);
     * $array->Insert(9, 1) and ArrayList then will contain [1,9,2,3]
     * ```
     */
    public function InsertAt(mixed $value, int $toIndex): void
    {
        array_splice($this->data, $toIndex, 0, array($value));
    }

    /**
     * Deletes an item
     * 
     * ```
     * $array = new ArrayList([1,2,3]);
     * $array->Delete(2) and ArrayList then will contain [1,3]
     * ```
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
     * 
     * ```
     * $array = new ArrayList([1,2,3]);
     * $array->DeleteAt(2) and ArrayList then will contain [1,2]
     * ```
     */
    public function DeleteAt(int $index): array
    {
        return array_splice($this->data, $index, 1);
    }

    /**
     * Clears an array list
     * 
     * ```
     * $array = new ArrayList([1,2,3]);
     * $array->Clear() and ArrayList then will not contain any item 
     * ```
     */
    public function Clear(): void
    {
        $this->data = array();
    }

    /**
     * Returns string representation of array list
     * 
     * ```
     * $array = new ArrayList([1,2,3]);
     * $array->ToString() returns '1,2,3' 
     * $array->ToString(';') returns '1;2;3' 
     * ```
     */
    public function ToString(string $splitter = ','): string
    {
        return implode($splitter, $this->data);
    }

    /**
     * Returns internal array
     * 
     * ```
     * $array = new ArrayList([1,2,3]);
     * $array->ToArray() returns [1,2,3] 
     * ```
     */
    public function ToArray(): array
    {
        return $this->data;
    }

    /**
     * Sorts an array using sort function
     * 
     * ```
     * $array = new ArrayList([3,2,1]);
     * $array->SortByClosure(fn($a, $b) => $a <=> $b) returns [1,2,3]
     *  
     * $array = new ArrayList([1,3,2]);
     * $array->SortByClosure(fn($a, $b) => $a > $b ? -1 : 1) returns [3,2,1] 
     * ```
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
     * 
     * 
     * ```
     * $array = new ArrayList([
     *   (object)['field1' => 1, 'field2' => 2],
     *   (object)['field1' => 2, 'field2' => 3],
     *   (object)['field1' => 5, 'field2' => 1],
     *   (object)['field1' => 7, 'field2' => 1],
     *   (object)['field1' => 2, 'field2' => 3],
     * ]);
     * 
     * $array->Sort('field1', SORT_ASC) returns 
     * 
     * ArrayList([
     *   (object)['field1' => 1, 'field2' => 2],
     *   (object)['field1' => 2, 'field2' => 3],
     *   (object)['field1' => 2, 'field2' => 3],
     *   (object)['field1' => 5, 'field2' => 1],
     *   (object)['field1' => 7, 'field2' => 1],
     * ])
     * 
     * $array->Sort('field1', SORT_DESC) returns
     * 
     * ArrayList([
     *   (object)['field1' => 7, 'field2' => 1],
     *   (object)['field1' => 5, 'field2' => 1],
     *   (object)['field1' => 2, 'field2' => 3],
     *   (object)['field1' => 2, 'field2' => 3],
     *   (object)['field1' => 1, 'field2' => 2],
     * ])
     *  
     * ```
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
     * 
     * ```
     * $array = new ArrayList([1,2,3]);
     * $array->Count() returns 3
     * ```
     */
    public function Count(): int
    {
        return count($this->data);
    }

    /**
     * Returns first item of array list
     * 
     * ```
     * $array = new ArrayList([1,2,3]);
     * $array->First() returns 1
     * ```
     */
    public function First(): mixed
    {
        return $this->Item(0);
    }

    /**
     * Returns last item of array list
     * 
     * ```
     * $array = new ArrayList([1,2,3]);
     * $array->Last() returns 3
     * ```
     */
    public function Last(): mixed
    {
        return $this->Item($this->Count() - 1);
    }

    /**
     * Return internal array for json conversion
     * 
     * ```
     * $array = new ArrayList([1,2,3]);
     * $array->jsonSerialize() returns [1,2,3]
     * ```
     */
    public function jsonSerialize(): array
    {
        return $this->ToArray();
    }

    /**
     * Filters an array list by closure
     * 
     * 
     * ```
     * $array = new ArrayList([1,2,3]);
     * $array->Filter(fn($v) => $v==3) returns [3]
     * ```
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
     * 
     * ```
     * $array = new ArrayList([1,2,3]);
     * $array->Filter(fn($v) => $v==3) returns 3
     * ```
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
     * 
     * ```
     * $array = new ArrayList([1,2,3]);
     * $array->Filter(fn($v) => $v*2) returns [2,4,6]
     * ```
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
     * 
     * ```
     * $array = new ArrayList([1,2,3]);
     * $array->offsetSet(2,4) returns [1,4,3]
     * ```
     * 
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
     * 
     * ```
     * $array = new ArrayList([1,2,3]);
     * $array->offsetExists(2) returns true
     * $array->offsetExists(4) returns false
     * ```
     * 
     * @param int $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return $offset < $this->Count();
    }

    /**
     * Deletes an item from array list by index (used for ArrayAccess)
     * 
     * ```
     * $array = new ArrayList([1,2,3]);
     * $array->offsetUnset(1) array goes to be [1,3]
     * ```
     * 
     * @param int $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->DeleteAt($offset);
    }

    /**
     * Returns an item at offset position
     * 
     * ```
     * $array = new ArrayList([1,2,3]);
     * $array->offsetGet(2) returns 3
     * ```
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