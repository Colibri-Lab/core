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
    protected $attributes;

    /**
     * Права доступа
     *
     * @var Security
     */
    protected $access;

    /**
     * Сеттер
     *
     * @param string $property свойство
     * @param mixed $value значение
     * @testFunction testNode__set
     */
    public function __set($property, $value)
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
    protected function getAttributesObject()
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
    protected function getSecurityObject()
    {
        if ($this->access === null) {
            $this->access = new Security($this);
        }
        return $this->access;
    }
}
