<?php

/**
 * Serialization
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2020 ColibriLab
 * @package Colibri\Xml\Serialization
 *
 */

namespace Colibri\Xml\Serialization;

use Colibri\Common\VariableHelper;

/**
 * Представляет собой десериализованный из xml обьект
 * @property string $name
 * @property array $attributes - аттрибуты
 * @property mixed $content - данные
 */
class XmlSerialized implements \JsonSerializable
{

    /**
     * Название элемента
     *
     * @var string
     */
    private string $_name;

    /**
     * Список атрибутов
     *
     * @var object
     */
    private ?object $_attributes;

    /**
     * Список элементов
     *
     * @var object|array
     */
    private object|array |null $_content;

    /**
     * Конструктор
     *
     * @param string $name название элемента
     * @param array $attributes список атрибутов
     * @param array $content контент
     * @testFunction testXmlSerialized
     */
    public function __construct(string $name = null, ?array $attributes = null, ?array $content = null)
    {
        $this->_name = $name;
        $this->_attributes = (object) $attributes;
        $this->_content = $content;
    }

    /**
     * Геттер
     *
     * @param string $property
     * @return mixed
     * @testFunction testXmlSerialized__get
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
     * Сеттер
     *
     * @param string $property
     * @param mixed $value
     * @testFunction testXmlSerialized__set
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
     * Возвращает обьект для последующей сериализации в json
     *
     * @return object
     * @testFunction testJsonSerialize
     */
    public function jsonSerialize(): object|array
    {
        return (object) array('class' => self::class, 'name' => $this->_name, 'content' => $this->_content, 'attributes' => $this->_attributes);
    }

    /**
     * Поднимает обьект из json
     *
     * @param string $jsonString строка в которую запакован обьект XmlSeralized
     * @return XmlSerialized|XmlCData|array|null
     */
    /**
     * @testFunction testJsonUnserialize
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