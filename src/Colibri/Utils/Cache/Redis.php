<?php

/**
 * Config
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2020 ColibriLab
 * @package Colibri\Utils\Cache
 *
 */

namespace Colibri\Utils\Cache;

/**
 * Memcached function encapsulator class.
 *
 */
class Redis
{
    /**
     * Static variable to ensure singleton mechanism.
     *
     * @var \Redis
     */
    public static $instance;

    /**
     * Creates a singleton Memcached object.
     *
     * @param string $host The Memcached host
     * @param int $port The Memcached port
     * @return \Redis|null The Memcached object or null if the class does not exist
     */
    public static function Create(string $host, int $port): ?\Redis
    {

        if (!\class_exists('Redis')) {
            return null;
        }

        if (!Redis::$instance) {
            Redis::$instance = new \Redis();
            Redis::$instance->connect($host, $port);
            // Redis::$instance->connect($host, $port);

        }
        return Redis::$instance;
    }

    /**
     * Closes the Memcached connection.
     *
     */
    public static function Dispose(): void
    {
        if (!Redis::$instance) {
            return;
        }
        if (Redis::$instance) {
            Redis::$instance->close();
            Redis::$instance = null;
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
        if (!Redis::$instance) {
            return false;
        }
        return (bool)Redis::$instance->exists($name);
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
        if (!Redis::$instance) {
            return false;
        }
        $value = serialize($value);

        return $livetime > 0
            ? Redis::$instance->setex($name, $livetime, $value)
            : Redis::$instance->set($name, $value);
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
        return Redis::Write($name, $value, $livetime);
    }

    /**
     * Deletes a variable from the cache.
     *
     * @param string $name The name of the variable
     * @return bool True if the operation was successful, otherwise false
     */
    public static function Delete(string $name): bool
    {
        if (!Redis::$instance) {
            return false;
        }
        return Redis::$instance->del($name);
    }

    /**
     * Reads a variable from the cache.
     *
     * @param string $name The name of the variable
     * @return mixed|false The data of the variable, or false if the variable does not exist in the cache
     */
    public static function Read(string $name): mixed
    {
        if (!Redis::$instance) {
            return false;
        }
        if (!Redis::Exists($name)) {
            return false;
        }
        return Redis::$instance->get($name);
    }

    public function getAllKeys(string $pattern = '*'): array
    {
        $it = null;
        $keys = [];

        while ($arr = Redis::$instance->scan($it, $pattern)) {
            foreach ($arr as $key) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    /**
     * Lists keys stored in the cache.
     *
     * @param string|null $filter The filter pattern to match keys against
     * @return array An array of keys stored in the cache
     */
    public static function List(?string $filter = null): array
    {
        if (!Redis::$instance) {
            return [];
        }

        $return = Redis::$instance->getAllKeys();
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
