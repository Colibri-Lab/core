<?php

/**
 * Definitions
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2020 ColibriLab
 * @package Colibri\Xml\Definitions
 *
 */

namespace Colibri\Xml\Definitions;

/**
 * Определени простого типа
 * 
 * @property-read string $name
 * @property-read object $restrictions
 * @testFunction testXsdBaseTypeDefinition
 */
class XsdBaseTypeDefinition implements \JsonSerializable
{

    /**
     * Базовый тип
     *
     * @var string
     */
    private string $_base;

    /**
     * Конструктор
     *
     * @param mixed $base базовый тип
     */
    public function __construct(mixed $base)
    {
        $this->_base = $base;
    }

    /**
     * Геттер
     *
     * @param string $name
     * @return mixed
     * @testFunction testXsdBaseTypeDefinition__get
     */
    public function __get(string $name): mixed
    {
        if (strtolower($name) == 'name') {
            return str_replace('xs:', '', $this->_base);
        }
        else if (strtolower($name) == 'restrictions') {
            return (object)['base' => $this->name];
        }
    }

    /**
     * Возвращает данные в виде простого обьекта для упаковки в json
     *
     * @return object
     * @testFunction testXsdBaseTypeDefinitionJsonSerialize
     */
    public function jsonSerialize(): object|array
    {
        return (object)array('name' => $this->name, 'restrictions' => $this->restrictions);
    }

    /**
     * Возвращает данные в виде простого обьекта
     *
     * @return object
     * @testFunction testXsdBaseTypeDefinitionToObject
     */
    public function ToObject(): object
    {
        return (object)array('name' => $this->name, 'restrictions' => $this->restrictions);
    }
}
