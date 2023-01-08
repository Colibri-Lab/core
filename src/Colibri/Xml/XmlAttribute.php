<?php

/**
 * Xml
 * 
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Xml
 * 
 */

namespace Colibri\Xml;

/**
 * Класс для работы с атрибутами
 * 
 * @property string $value значение атрибута
 * @property-read string $name название атрибута
 * @property-read string $type тип атрибута
 * @property-read \DOMNode $raw узел
 * @testFunction testXmlAttribute
 */
class XmlAttribute
{

    /**
     * Обьект содержающий DOMNode атрибута
     *
     * @var mixed
     */
    private mixed $_data;

    /**
     * Конструктор
     *
     * @param \DOMNode $data raw узел для инициализации врапера
     */
    public function __construct(\DOMNode $data)
    {
        $this->_data = $data;
    }

    /**
     * Getter
     *
     * @param string $property название свойства
     * @return mixed
     * @testFunction testXmlAttribute__get
     */
    public function __get(string $property): mixed
    {
        switch (strtolower($property)) {
            case 'value':
                return $this->_data->nodeValue;
            case 'name':
                return $this->_data->nodeName;
            case 'type':
                return $this->_data->nodeType;
            case 'raw':
                return $this->_data;
            default:
                break;
        }
        return null;
    }

    /**
     * Setter
     *
     * @param string $property название свойства
     * @param string $value значение свойства
     * @return void
     * @testFunction testXmlAttribute__set
     */
    public function __set(string $property, mixed $value): void
    {
        if (strtolower($property) == 'value') {
            $this->_data->nodeValue = $value;
        }
    }

    /**
     * Удаляет атрибут
     *
     * @return void
     * @testFunction testXmlAttributeRemove
     */
    public function Remove(): void
    {
        $this->_data->parentNode->removeAttributeNode($this->_data);
    }
}