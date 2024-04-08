<?php

/**
 * Mem
 * 
 * Encapsulates Memcached functions.
 * 
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Utils\Cache
 */
namespace Colibri\Utils\Cache;

/**
 * Memcached function encapsulator class.
 *
 */
class Mem
{

    /**
     * Static variable to ensure singleton mechanism.
     *
     * @var \Memcached
     */
    static $instance;

    /**
     * Creates a singleton Memcached object.
     *
     * @param string $host The Memcached host
     * @param int $port The Memcached port
     * @return \Memcached|null The Memcached object or null if the class does not exist
     */
    public static function Create(string $host, int $port): ?\Memcached
    {

        if (!\class_exists('Memcached')) {
            return null;
        }

        if (!Mem::$instance) {
            Mem::$instance = new \Memcached();
            Mem::$instance->addServer($host, $port);
            // Mem::$instance->connect($host, $port);
            
        }
        return Mem::$instance;
    }

    /**
     * Closes the Memcached connection.
     *
     */
    public static function Dispose(): void
    {
        if (!Mem::$instance) {
            return;
        }
        if (Mem::$instance) {
            Mem::$instance->close();
            Mem::$instance = null;
        }
    }

    /**
     * Checks the existence of a variable in the cache.
     *
     * @param string $name The name of the variable
     * @return bool True if the variable exists in the cache, otherwise false
     */
    public static function Exists(string $name): bool
    {
        if (!Mem::$instance) {
            return false;
        }
        $cacheData = Mem::$instance->get($name);
        if (!$cacheData) {
            return false;
        }
        return true;
    }

    /**
     * Writes a variable to the cache.
     *
     * @param string $name The name of the variable
     * @param mixed $value The data
     * @param int $livetime The lifetime of the variable in seconds
     * @return bool True if the operation was successful, otherwise false
     */
    public static function Write(string $name, mixed $value, int $livetime = 600): bool
    {
        if (!Mem::$instance) {
            return false;
        }
        if (!Mem::Exists($name)) {
            return Mem::$instance->add($name, $value, $livetime);
        } else {
            return Mem::$instance->set($name, $value, $livetime);
        }

    }

    /**
     * Writes a variable to the cache in compressed form.
     *
     * @param string $name The name of the variable
     * @param mixed $value The data
     * @param int $livetime The lifetime of the variable in seconds
     * @return bool True if the operation was successful, otherwise false
     */
    public static function ZWrite(string $name, mixed $value, int $livetime = 600): bool
    {
        if (!Mem::$instance) {
            return false;
        }
        // MEMCACHE_COMPRESSED = 2
        return Mem::$instance->add($name, $value, $livetime);
    }

    /**
     * Deletes a variable from the cache.
     *
     * @param string $name The name of the variable
     * @return bool True if the operation was successful, otherwise false
     */
    public static function Delete(string $name): bool
    {
        if (!Mem::$instance) {
            return false;
        }
        return Mem::$instance->delete($name);
    }

    /**
     * Reads a variable from the cache.
     *
     * @param string $name The name of the variable
     * @return mixed|false The data of the variable, or false if the variable does not exist in the cache
     */
    public static function Read(string $name): mixed
    {
        if (!Mem::$instance) {
            return false;
        }
        if (!Mem::Exists($name)) {
            return false;
        }
        return Mem::$instance->get($name);
    }

    /**
     * Lists keys stored in the cache.
     *
     * @param string|null $filter The filter pattern to match keys against
     * @return array An array of keys stored in the cache
     */
    public static function List(?string $filter = null): array
    {
        if (!Mem::$instance) {
            return [];
        }
        
        $return = Mem::$instance->getAllKeys();
        if(!$filter) {
            return $return;
        }

        $ret = [];
        foreach($return as $item) {
            if(preg_match('/'.$filter.'/', $item)) {
                $ret[] = $item;
            }
        }
        return $ret;
    }

}