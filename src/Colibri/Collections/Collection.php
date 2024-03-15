<?php

/**
 * Collection
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
use InvalidArgumentException;
use IteratorAggregate;
use JsonSerializable;

/**
 * Base class for collections
 */
class Collection implements ICollection, IteratorAggregate, JsonSerializable, ArrayAccess, Countable
{

    /**
     * Collection internal data
     */
    protected mixed $data = null;

    /**
     * Initializes a collection from an array, stdClass, or ICollection.
     * 
     * ```
     * $collection1 = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
     * or copy collection 
     * $collection2 = new Collection($collection1);
     * ```
     *
     * @param mixed $data The data to initialize the collection.
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
     * Checks if the key exists
     * 
     * ```
     * $collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
     * $collection->Exists('a') returns true
     * $collection->Exists('g') returns false
     * ```
     */
    public function Exists(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Checks if the specified item exists in the data array.
     * 
     * ```
     * $collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
     * $collection->Contains(1) returns true
     * $collection->Contains(10) returns false
     * ```
     *
     * @param mixed $item The item to search for.
     * @return bool True if the item is found, false otherwise.
     */
    public function Contains(mixed $item): bool
    {
        return in_array($item, $this->data, true);
    }

    /**
     * Searches for the specified item in the data array and returns its index.
     * 
     * ```
     * $collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
     * $collection->IndexOf(1) returns 'a'
     * $collection->IndexOf(10) returns null
     * ```
     *
     * @param mixed $item The item to search for.
     * @return mixed|null The index of the item if found, or null if not found.
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
     * Retrieves the key at the specified index in the data array.
     * 
     * ```
     * $collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
     * $collection->Key(1) returns 'b'
     * $collection->Key(10) returns null
     * ```
     *
     * @param int $index The index of the key to retrieve.
     * @return string|null The key if found, or null if the index is out of bounds.
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
     * Retrieves the value associated with the specified key in the data array.
     * 
     * ```
     * $collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
     * $collection->Item('a') returns 1
     * $collection->Item('g') returns null
     * ```
     *
     * @param string $key The key to retrieve the value for.
     * @return mixed|null The value if found, or null if the key does not exist.
     */
    public function Item(string $key): mixed
    {
        if ($this->Exists($key)) {
            return $this->data[$key];
        }
        return null;
    }

    /**
     * Retrieves the value at the specified index in the data array.
     * 
     * ```
     * $collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
     * $collection->ItemAt(1) returns 2
     * $collection->ItemAt(10) returns null
     * ```
     *
     * @param int $index The index to retrieve the value from.
     * @return mixed|null The value if found, or null if the index is out of bounds.
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
     * Returns an iterator for traversing the collection data.
     * 
     * ```
     * $collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
     * foreach($collection as $key => $value) { ... }
     * or
     * foreach($collection->getIterator() as $key => $value) { ... }
     * ```
     *
     * @return CollectionIterator An iterator for the collection.
     */
    public function getIterator(): CollectionIterator
    {
        return new CollectionIterator($this);
    }

    /**
     * Adds a key-value pair to the collection.
     * 
     * ```
     * $collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
     * $collection->Add('g', 10) collection goes to be ['a' => 1, 'b' => 2, 'c' => 3, 'g' => 10]
     * ```
     *
     * @param string $key The key to add.
     * @param mixed $value The value associated with the key.
     * @return mixed The updated collection data.
     */
    public function Add(string $key, mixed $value): mixed
    {
        $this->data[strtolower($key)] = $value;
        return $value;
    }

    /**
     * Appends data from another source to the collection.
     * 
     * ```
     * $collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
     * $collection->Append(['g' => 10, 'c' => 11]) collection goes to be ['a' => 1, 'b' => 2, 'c' => 3, 'g' => 10, 'c' => 11]
     * ```
     *
     * @param mixed $from The data source to append.
     * @return void
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
     * Inserts a key-value pair at the specified index in the data array.
     * 
     * ```
     * $collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
     * $collection->Insert(1, 'g', 10) collection goes to be ['a' => 1, 'g' => 10, 'b' => 2, 'c' => 3]
     * ```
     *
     * @param mixed $index The index where the key-value pair should be inserted.
     * @param mixed $key The key to insert.
     * @param mixed $value The value associated with the key.
     * @return mixed The updated collection data.
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
     * Removes the key-value pair with the specified key from the collection.
     * 
     * ```
     * $collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
     * $collection->Delete('b') collection goes to be ['a' => 1, 'c' => 3]
     * ```
     *
     * @param string $key The key to delete.
     * @return bool True if the key was successfully deleted, false otherwise.
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
     * Removes the key-value pair at the specified index from the collection.
     * 
     * ```
     * $collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
     * $collection->DeleteAt(1) collection goes to be ['a' => 1, 'c' => 3]
     * ```
     *
     * @param int $index The index of the key-value pair to delete.
     * @return bool True if the key-value pair was successfully deleted, false otherwise.
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
     * Clears empty values.
     *
     * @return void
     */
    public function Clean(): void
    {
        while (($index = $this->IndexOf('')) > -1) {
            array_splice($this->data, $index, 1);
        }
    }

    /**
     * Clears the collection by removing all key-value pairs.
     * 
     * ```
     * $collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
     * $collection->Clear() collection goes to be []
     * ```
     *
     * @return void
     */
    public function Clear(): void
    {
        $this->data = array();
    }

    /**
     * Converts the collection data to a string representation.
     * 
     * ```
     * $collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
     * $collection->ToString(['=', '&']) returns 'a=1&b=2&c=3'
     * $collection->ToString(['=', '&'], fn($k,$v) => $v*2) returns 'a=2&b=4&c=6'
     * ```
     *
     * @param array|null $splitters Optional array of splitters to join the data elements.
     * @param mixed|null $mapFunction Optional mapping function to apply to each data element.
     * @return string The string representation of the collection data.
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
     * Creates a collection from a string representation.
     * 
     * ```
     * $collection = Collection::FromString('a=1&b=2&c=3', ['=','&']);
     * $collection is ['a' => 1, 'b' => 2, 'c' => 3]
     * ```
     *
     * @param string $string The string containing data to initialize the collection.
     * @param array|null $splitters Optional array of splitters to parse the string data.
     * @return Collection The initialized collection.
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
     * Converts the collection data to an array.
     * 
     * ```
     * $collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
     * $collection->ToArray() returns ['a' => 1, 'b' => 2, 'c' => 3]
     * ```
     *
     * @return array The array representation of the collection data.
     */
    public function ToArray(): array
    {
        return $this->data;
    }

    /**
     * Returns the number of elements in the collection.
     * 
     * ```
     * $collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
     * $collection->Count() returns 3
     * ```
     *
     * @return int The count of elements in the collection.
     */
    public function Count(): int
    {
        return count($this->data);
    }

    /**
     * Retrieves the first element from the collection.
     * 
     * ```
     * $collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
     * $collection->First() returns 1
     * ```
     *
     * @return mixed|null The first element if the collection is not empty, or null if empty.
     */
    public function First(): mixed
    {
        return $this->ItemAt(0);
    }

    /**
     * Retrieves the last element from the collection.
     * 
     * ```
     * $collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
     * $collection->Last() returns 3
     * ```
     *
     * @return mixed|null The last element if the collection is not empty, or null if empty.
     */
    public function Last(): mixed
    {
        return $this->ItemAt($this->Count() - 1);
    }

    /**
     * Magic method to retrieve the value of a property by its name.
     *
     * @param string $property The name of the property to retrieve.
     * @return mixed|null The value of the property if found, or null if the property does not exist.
     */
    public function __get(string $property): mixed
    {
        return $this->Item(strtolower($property));
    }

    /**
     * Magic method to set the value of a property by its name.
     *
     * @param string $key The name of the property to set.
     * @param mixed $value The value to assign to the property.
     * @return void
     */
    public function __set(string $key, mixed $value): void
    {
        $this->Add($key, $value);
    }

    /**
     * Filters the collection data based on the provided closure.
     * 
     * ```
     * $collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
     * $collection->Filter(fn($k,$v) => $k==='a' && $v===1) returns ['a' => 1]
     * ```
     *
     * @param \Closure $closure The closure used for filtering.
     * @return Collection The filtered collection.
     */
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

    /**
     * Extracts data from a specified page using the given page size.
     * 
     * ```
     * $collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
     * $collection->Extract(1, 2) returns ['a' => 1, 'b' => 2]
     * $collection->Extract(2, 2) returns ['c' => 3]
     * ```
     *
     * @param mixed $page The page to extract data from.
     * @param mixed $pagesize The size of the page.
     * @return Collection The extracted data collection.
     */
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

    /**
     * Serializes the object to a value that can be natively serialized by json_encode().
     * 
     * ```
     * $collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
     * $collection->jsonSerialize() returns ['a' => 1, 'b' => 2, 'c' => 3]
     * ```
     *
     * @return mixed The serialized data, which can be of any type other than a resource.
     */
    public function jsonSerialize(): array
    {
        return $this->ToArray();
    }

    /**
     * Sets the value at the specified offset in the data array.
     *
     * @param mixed $offset The offset where the value should be set.
     * @param mixed $value The value to assign to the specified offset.
     * @return void
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
     * Checks whether an offset exists.
     *
     * This method is executed when using `isset()` or `empty()`
     * on objects implementing the `ArrayAccess` interface.
     *
     * @param mixed $offset The offset (index) to check for existence.
     * @return bool Returns `true` if the offset exists, and `false` otherwise.
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
     * Unsets the value at the specified index.
     *
     * @param mixed $key The index being unset.
     * @return void
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
     * Retrieves the element from the collection at the specified index.
     *
     * @param mixed $key The index to access the element.
     * @return mixed|null The collection element or null if the element is not found.
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