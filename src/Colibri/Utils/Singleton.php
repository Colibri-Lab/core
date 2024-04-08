<?php

/**
 * Utilities
 *
 * This class represents an iterator for objects.
 * It allows iterating over the properties of an object.
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
     * The singleton instance.
     *
     * @var static
     */
    public static $instance;

    /**
     * Creates an instance of the singleton class if it does not already exist,
     * and returns the instance.
     *
     * @param mixed ...$arguments Arguments to be passed to the constructor, if needed.
     * @return static The singleton instance.
     */
    public static function Create(...$arguments): static
    {
        if (!static::$instance) {
            static::$instance = new static(...$arguments);
        }
        return static::$instance;
    }
    
}