<?php

/**
 * FileSystem
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\IO\FileSystem
 */

namespace Colibri\IO\FileSystem;

/**
 * Базовый класс для File и Directory
 * @testFunction testNode
 */
class Node
{

    /**
     * Атрибуты
     *
     * @var Attributes
     */
    protected ? Attributes $attributes = null;

    /**
     * Права доступа
     *
     * @var Security
     */
    protected ? Security $access = null;

    /**
     * Сеттер
     *
     * @param string $property свойство
     * @param mixed $value значение
     * @testFunction testNode__set
     */
    public function __set(string $property, mixed $value): void
    {
        switch (strtolower($property)) {
            case 'created':
                $this->getAttributesObject()->created = $value;
                break;
            case 'modified':
                $this->getAttributesObject()->modified = $value;
                break;
            case 'readonly':
                $this->getAttributesObject()->readonly = $value;
                break;
            case 'hidden':
                $this->getAttributesObject()->hidden = $value;
                break;
            default: {
                    break;
                }
        }
    }

    /**
     * Загружает данные об атрибутах
     *
     * @return Attributes
     * @testFunction testNodeGetAttributesObject
     */
    protected function getAttributesObject(): Attributes
    {
        if ($this->attributes === null) {
            $this->attributes = new Attributes($this);
        }
        return $this->attributes;
    }

    /**
     * Загружает данные о правах доступа
     *
     * @return Security
     * @testFunction testNodeGetSecurityObject
     */
    protected function getSecurityObject(): Security
    {
        if ($this->access === null) {
            $this->access = new Security($this);
        }
        return $this->access;
    }

    public static function Link(string $sourcePath, string $destPath, bool $recursive = true, string $mode = '777')
    {
        if(!file_exists($destPath) && file_exists($sourcePath)) {
            $path2 = dirname($destPath[strlen($destPath) - 1] == '/' ? $destPath . '#' : $destPath);
            shell_exec('mkdir' . ($recursive ? ' -p' : '') . ' -m' . $mode . ' ' . $path2);
            symlink($sourcePath, $destPath);
        }
    }

}