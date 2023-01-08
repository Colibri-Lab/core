<?php

namespace Colibri\Utils\Cache;

/**
 * Класс инкапсулятор функций мемкэш
 * @testFunction testMem
 */
class Mem
{

    /**
     * Статисчекая переменная для обеспечения синглтон механизма
     *
     * @var \Memcache
     */
    static $instance;

    /**
     * Создает синглтон обьект мемкэш
     *
     * @testFunction testMemCreate
     */
    public static function Create(string $host, int $port): ?\Memcache
    {

        if (!\class_exists('Memcache')) {
            return null;
        }

        if (!Mem::$instance) {
            Mem::$instance = new \Memcache();
            Mem::$instance->connect($host, $port);
        }
        return Mem::$instance;
    }

    /**
     * Закрывает соединение с мемкэш
     *
     * @testFunction testMemDispose
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
     * Проверяет наличие переменной в кэше
     *
     * @param string $name - название переменной
     * @return boolean
     * @testFunction testMemExists
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
     * Сохраняет переменную в кэш
     *
     * @param string $name - название переменной
     * @param mixed $value - данные
     * @param int $livetime - время жизни
     * @return boolean
     * @testFunction testMemWrite
     */
    static function Write(string $name, mixed $value, int $livetime = 600): bool
    {
        if (!Mem::$instance) {
            return false;
        }

        if (!Mem::Exists($name)) {
            return Mem::$instance->add($name, $value, false, $livetime);
        } else {
            return Mem::$instance->set($name, $value, false, $livetime);
        }

    }

    /**
     * Сохраняет переменную в кэш в архивированном виде
     *
     * @param string $name - название переменной
     * @param mixed $value - данные
     * @param int $livetime - время жизни
     * @return boolean
     * @testFunction testMemZWrite
     */
    static function ZWrite(string $name, mixed $value, int $livetime = 600): bool
    {
        if (!Mem::$instance) {
            return false;
        }
        return Mem::$instance->add($name, $value, \MEMCACHE_COMPRESSED, $livetime);
    }

    /**
     * Удаляет переменную из кэша
     *
     * @param string $name - название переменной
     * @return mixed | boolean
     * @testFunction testMemDelete
     */
    static function Delete(string $name): bool
    {
        if (!Mem::$instance) {
            return false;
        }
        return Mem::$instance->delete($name);
    }

    /**
     * Считывает переменную из кэша, если перенной нет, возвращает false
     *
     * @param string $name
     * @return mixed
     * @testFunction testMemRead
     */
    static function Read(string $name): mixed
    {
        if (!Mem::$instance) {
            return false;
        }
        if (!Mem::Exists($name)) {
            return false;
        }
        return Mem::$instance->get($name);
    }
}