<?php

/**
 * Serialization
 *
 * This class represents a deserialized object from XML.
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2020 ColibriLab
 * @package Colibri\Xml\Serialization
 */
namespace Colibri\Xml\Serialization;

use Colibri\Common\VariableHelper;

/**
 * Serialization
 *
 * This class represents a deserialized object from XML.
 * 
 * @property string $name
 * @property array $attributes - аттрибуты
 * @property mixed $content - данные
 */
class XmlSerialized implements \JsonSerializable
{

    /**
     * The name of the element.
     *
     * @var string
     */
    private string $_name;

    /**
     * The list of attributes.
     *
     * @var object
     */
    private ?object $_attributes;

    /**
     * The list of elements.
     *
     * @var object|array|null
     */
    private object|array |null $_content;

    /**
     * Constructor.
     *
     * @param string|null $name       The name of the element.
     * @param array|null  $attributes The list of attributes.
     * @param array|null  $content    The content.
     */
    public function __construct(string $name = null, ?array $attributes = null, ?array $content = null)
    {
        $this->_name = $name;
        $this->_attributes = (object) $attributes;
        $this->_content = $content;
    }

    /**
     * Getter.
     *
     * @param string $property The property name.
     * @return mixed|null The value of the property.
     */
    public function __get(string $property): mixed
    {
        if (strtolower($property) == 'attributes') {
            return $this->_attributes;
        } elseif (strtolower($property) == 'content') {
            return $this->_content;
        } elseif (strtolower($property) == 'name') {
            return $this->_name;
        }
        return null;
    }

    /**
     * Setter.
     *
     * @param string $property The property name.
     * @param mixed  $value    The value to set.
     */
    public function __set(string $property, mixed $value): void
    {
        if (strtolower($property) == 'attributes') {
            $this->_attributes = (object) $value;
        } elseif (strtolower($property) == 'content') {
            $this->_content = $value;
        } elseif (strtolower($property) == 'name') {
            $this->_name = $value;
        } else {
            if (!is_array($this->_content)) {
                $this->_content = array();
            }
            $this->_content[$property] = $value;
        }
    }

    /**
     * Returns the object for JSON serialization.
     *
     * @return object The object representation of XmlSerialized.
     */
    public function jsonSerialize(): object|array
    {
        return (object) array('class' => self::class, 'name' => $this->_name, 'content' => $this->_content, 'attributes' => $this->_attributes);
    }

    /**
     * Unserializes the object from JSON.
     *
     * @param string|array $jsonString The JSON string or array.
     * @return XmlSerialized|XmlCData|array|null The unserialized object.
     */
    public static function jsonUnserialize(string $jsonString): XmlSerialized|XmlCData|array |null
    {
        $object = is_string($jsonString) ? json_decode($jsonString, true) : $jsonString;
        if (is_null($object)) {
            return null;
        }

        if (isset($object['class'])) {
            // если это мой обьект
            $className = $object['class'];
            if ($className == 'XmlCData') {
                return new XmlCData($object['value']);
            } else {
                $class = new $className;
                foreach ($object as $key => $value) {
                    if ($key !== 'class') {
                        $class->$key = XmlSerialized::jsonUnserialize(json_encode($value));
                    }
                }
                return $class;
            }
        } elseif (!is_array($object)) {
            return $object;
        } elseif (VariableHelper::IsAssociativeArray($object)) {
            $ret = [];
            foreach ($object as $key => $value) {
                $ret[$key] = XmlSerialized::jsonUnserialize(json_encode($value));
            }
            return $ret;
        } elseif (is_array($object)) {
            $ret = [];
            foreach ($object as $value) {
                $ret[] = XmlSerialized::jsonUnserialize(json_encode($value));
            }
            return $ret;
        }
        return $object;
    }
}