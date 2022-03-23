<?php

/**
 * Коллекция без возможности записи
 * 
 * @author Ваган Григорян <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Collections
 * @version 1.0.0
 * 
 */

namespace Colibri\Collections;

/**
 * Коллекция без возможности записи
 * @testFunction testReadonlyCollection
 */
class ReadonlyCollection extends Collection
{

    /**
     * Очистить пустые значения
     *
     * @return void
     * @testFunction testReadonlyCollectionClean
     */
    public function Clean()
    {
        while (($index = $this->IndexOf('')) > -1) {
            array_splice($this->data, $index, 1);
        }
    }

    /**
     * Блокирует добавление значений в коллекцию
     *
     * @param string $key
     * @param mixed $value
     * @return void
     * @throws CollectionException
     * @testFunction testReadonlyCollectionAdd
     */
    public function Add($key, $value)
    {
        throw new CollectionException('This is a readonly collection');
    }
    /**
     * Блокирует удаление значений в коллекцию
     *
     * @param string $key
     * @return void
     * @throws CollectionException
     * @testFunction testReadonlyCollectionDelete
     */
    public function Delete($key)
    {
        throw new CollectionException('This is a readonly collection');
    }
}
