<?php

/**
 * Interface for lists
 * 
 * @author VaHan Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Collections
 * @version 1.0.0
 * 
 */

namespace Colibri\Collections;

/**
 * Interface for lists
 */
interface IArrayList
{
    /**
     * Retrieves an item from the collection at the specified index.
     *
     * @param int $index The index of the item to retrieve.
     * @return mixed The item at the specified index.
     */
    public function Item(int $index): mixed;

    /**
     * Adds an item to the collection.
     *
     * @param mixed $value The item to add.
     * @return mixed The updated collection after adding the item.
     */
    public function Add(mixed $value): mixed;
    
    /**
     * Appends one or many items to the end of list.
     *
     * @param mixed $nodes The node(s) to append.
     * @return void
     */
    public function Append(mixed $values): void;

    /**
     * Deletes an item from the collection.
     *
     * @param mixed $value The item to delete.
     * @return bool True if the item was successfully deleted, false otherwise.
     */
    public function Delete(mixed $value): bool;

    /**
     * Deletes an item from the collection at the specified index.
     *
     * @param int $index The index of the item to delete.
     * @return array The updated collection after removing the item.
     */
    public function DeleteAt(int $index): array;

    /**
     * Converts the object to a string representation.
     *
     * @param string $splitter The delimiter to use when joining elements.
     * @return string The string representation of the object.
     */
    public function ToString(string $splitter = ','): string;

    /**
     * Returns an array of values.
     *
     * @return array
     */
    public function ToArray(): array;

    /**
     * Returns the number of records in the array.
     *
     * @return int
     */
    public function Count(): int;

    /**
     * Returns the first item in the list.
     * @return mixed 
     */
    public function First(): mixed;

    /**
     * Returns the last item in the list.
     * @return mixed 
     */
    public function Last(): mixed;
    
}