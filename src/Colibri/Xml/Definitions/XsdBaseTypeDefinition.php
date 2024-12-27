<?php

/**
 * XsdBaseTypeDefinition
 *
 * Represents the definition of a simple type.
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2020 ColibriLab
 * @package Colibri\Xml\Definitions
 *
 */

namespace Colibri\Xml\Definitions;

/**
 * XsdBaseTypeDefinition
 *
 * Represents the definition of a simple type.
 *
 * @property-read string $name The name of the simple type.
 * @property-read object $restrictions The restrictions applied to the simple type.
 */
class XsdBaseTypeDefinition implements \JsonSerializable
{
    /**
     * The base type.
     *
     * @var string
     */
    private string $_base;

    /**
     * Constructor.
     *
     * @param mixed $base The base type.
     */
    public function __construct(mixed $base)
    {
        $this->_base = $base;
    }

    /**
     * Getter.
     *
     * @param string $name The name of the property.
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        if (strtolower($name) == 'name') {
            return str_replace('xs:', '', $this->_base);
        } elseif (strtolower($name) == 'restrictions') {
            return (object) ['base' => $this->name];
        }
        return null;
    }

    /**
     * Returns the data as a plain object for JSON serialization.
     *
     * @return object
     */
    public function jsonSerialize(): object|array
    {
        return (object) array('name' => $this->name, 'restrictions' => $this->restrictions);
    }

    /**
     * Returns the data as a plain object.
     *
     * @return object
     */
    public function ToObject(): object
    {
        return (object) array('name' => $this->name, 'restrictions' => $this->restrictions);
    }
}
