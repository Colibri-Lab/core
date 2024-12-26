<?php

/**
 * Collections
 *
 * @author Vahan Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Collections
 */

namespace Colibri\Collections;

/**
 * Interface for associative arrays
 */
interface ICollection
{

    /**
     * Checks if a key exists in the array.
     * @param string $key
     * @return bool
     */
    public function Exists(string $key): bool;

    /**
     * Return the key at the specified index.
     * @param int $index
     * @return string|null
     */
    public function Key(int $index): ?string;

    /**
     * Return the value associated with the given key.
     * @param string $key
     * @return mixed
     */
    public function Item(string $key): mixed;

    /**
     * Return the value at the specified index.
     * @param int $index
     * @return mixed
     */
    public function ItemAt(int $index): mixed;

    /**
     * Add a key-value pair; if the key already exists, it will be replaced.
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function Add(string $key, mixed $value): mixed;
    /**
     * Remove a key and its associated value from the array.
     * @param string $key
     * @return bool
     */
    public function Delete(string $key): bool;

    /**
     * Convert to a string with specified delimiters.
     * @param string[]|null $splitters
     * @return string
     */
    public function ToString(array $splitters = null): string;
    
    /**
     * Return the data as a regular array.
     * @return array
     */
    public function ToArray(): array;
    
    /**
     * Return the number of elements in the array.
     * @return int
     */
    public function Count(): int;
    
}
