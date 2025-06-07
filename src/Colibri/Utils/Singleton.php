<?php

/**
 * Utilities
 *
 * @package Colibri\Utils\Performance
 * @author Vahan P. Grigoryan
 * @copyright 2020 ColibriLab
 *
 */

namespace Colibri\Utils;

/**
 * Singleton
 *
 * This abstract class represents the Singleton design pattern.
 * It ensures that only one instance of a class is created and provides a global access point to that instance.
 */
abstract class Singleton
{
    
    /**
     * Creates an instance of the singleton class if it does not already exist,
     * and returns the instance.
     *
     * @param mixed ...$arguments Arguments to be passed to the constructor, if needed.
     * @return static The singleton instance.
     */
    final public static function Instance(...$arguments): static
    {
        static $instances = [];
        $className = static::class;
        if (!isset($instances[$className])) {
            $instances[$className] = new $className(...$arguments);
        }
        return $instances[$className];
    }

}
