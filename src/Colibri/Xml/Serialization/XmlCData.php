<?php

/**
 * Serialization
 *
 * This class represents a representation for a CDATA element.
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2020 ColibriLab
 * @package Colibri\Xml\Serialization
 */

namespace Colibri\Xml\Serialization;

/**
 * XmlCData
 *
 * This class represents a representation for a CDATA element.
 *
 */
class XmlCData implements \JsonSerializable
{
    /**
     * The value of the CDATA element.
     *
     * @var string
     */
    public string $value;

    /**
     * Constructor.
     *
     * @param string|null $value The value of the CDATA element.
     */
    public function __construct(?string $value = null)
    {
        $this->value = $value;
    }

    /**
     * Returns the data as a simple object for JSON serialization.
     *
     * @return object The object representation of the CDATA element.
     */
    public function jsonSerialize(): object|array
    {
        return (object) array('class' => self::class, 'value' => $this->value);
    }
}
