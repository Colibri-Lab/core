<?php

/**
 * Xml
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2020 ColibriLab
 * @package Colibri\Xml
 *
 */

namespace Colibri\Xml;

use Colibri\Common\StringHelper;
use Colibri\Utils\Debug;

/**
 * Список атрибутов
 * @property-read int $count
 * @testFunction testXmlNodeAttributeList
 */
class XmlNodeAttributeList implements \IteratorAggregate, \Countable
{

    /**
     * Документ
     *
     * @var \DOMDocument
     */
    private ?\DOMDocument $_document;

    /**
     * Нода
     *
     * @var mixed
     */
    private mixed $_node;

    /**
     * Список атрибутов
     *
     * @var \DOMNamedNodeMap
     */
    private ?\DOMNamedNodeMap $_data;

    /**
     * Конструктор
     *
     * @param \DOMDocument $document документ
     * @param \DOMNode $node узел
     * @param \DOMNamedNodeMap $xmlattributes список атрибутов
     */
    public function __construct(\DOMDocument $document, \DOMNode $node, \DOMNamedNodeMap $xmlattributes)
    {
        $this->_document = $document;
        $this->_node = $node;
        $this->_data = $xmlattributes;
    }

    /**
     * Возвращает итератор для обхода методом foreach
     *
     * @return XmlNodeListIterator
     * @testFunction testXmlNodeAttributeListGetIterator
     */
    public function getIterator(): XmlNodeListIterator
    {
        return new XmlNodeListIterator($this);
    }

    /**
     * Возвращает атрибут по индексу
     *
     * @param int $index
     * @return XmlAttribute
     * @testFunction testXmlNodeAttributeListItem
     */
    public function Item(int $index): XmlAttribute
    {
        return new XmlAttribute($this->_data->item($index));
    }

    /**
     * Возвращает количество атрибутов
     *
     * @return int
     * @testFunction testXmlNodeAttributeListCount
     */
    public function Count(): int
    {
        return $this->_data->length;
    }

    /**
     * Геттер
     *
     * @param string $property
     * @return XmlAttribute|null
     * @testFunction testXmlNodeAttributeList__get
     */
    public function __get(string $property): mixed
    {
        $attr = $this->_data->getNamedItem($property);
        if (!is_null($attr)) {
            return new XmlAttribute($attr);
        }

        $property = StringHelper::FromCamelCaseAttr($property);
        $attr = $this->_data->getNamedItem($property);
        if (!is_null($attr)) {
            return new XmlAttribute($attr);
        }
        return null;
    }

    /**
     * Добавляет атрибут
     *
     * @param string $name название атрибута
     * @param string $value значение атрибута
     * @return void
     * @testFunction testXmlNodeAttributeListAppend
     */
    public function Append(string $name, string $value): void
    {
        $attr = $this->_document->createAttribute($name);
        $attr->value = $value;
        $this->_node->appendChild($attr);
    }

    /**
     * Удаляет аттрибут по имени
     *
     * @param string $name название атрибута
     * @return void
     * @testFunction testXmlNodeAttributeListRemove
     */
    public function Remove(string $name): void
    {
        if ($this->$name && $this->$name->raw) {
            $this->_node->removeAttributeNode($this->$name->raw);
        }
    }




}
