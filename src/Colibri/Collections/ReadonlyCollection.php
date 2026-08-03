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
 * Collection with read-only capability.
 * @class
 * @extends Collection
 */
class ReadonlyCollection extends Collection
{
    /**
     * Prevents adding values to the collection.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     * @throws CollectionException
     * @public
     */
    public function Add(string $key, mixed $value): mixed
    {
        throw new CollectionException('This is a readonly collection');
    }

    /**
     * Prevents deleting values from the collection.
     *
     * @param string $key
     * @return void
     * @throws CollectionException
     * @public
     */
    public function Delete(string $key): bool
    {
        throw new CollectionException('This is a readonly collection');
    }

    /**
     * Prevents appending values to the collection.
     *
     * @param mixed $from
     * @return void
     * @throws CollectionException
     * @public
     */
    public function Append(mixed $from): void
    {
        throw new CollectionException('This is a readonly collection');
    }

    /**
     * Prevents inserting values to the collection.
     *
     * @param mixed $index
     * @param mixed $key
     * @param mixed $value
     * @return mixed
     * @throws CollectionException
     * @public
     */
    public function Insert(mixed $index, mixed $key, mixed $value): mixed
    {
        throw new CollectionException('This is a readonly collection');
    }

    /**
     * Prevents deleting values from the collection.
     *
     * @param int $index
     * @return bool
     * @throws CollectionException
     * @public
     */
    public function DeleteAt(int $index): bool
    {
        throw new CollectionException('This is a readonly collection');
    }

    /**
     * Prevents clearing the collection.
     *
     * @return void
     * @throws CollectionException
     * @public
     */
    public function Clear(): void
    {
        throw new CollectionException('This is a readonly collection');
    }

    /**
     * Prevents setting item into collection
     *
     * @param mixed $offset
     * @param mixed $value
     * @return void
     * @throws CollectionException
     * @public
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new CollectionException('This is a readonly collection');
    }

    /**
     * Prevents unsetting item in collection
     *
     * @param mixed $offset
     * @return void
     * @throws CollectionException
     * @public
     */
    public function offsetUnset(mixed $offset): void
    {
        throw new CollectionException('This is a readonly collection');
    }

}
