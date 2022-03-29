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

/**
 * Список узлов
 *
 * @property-read \DOMDocument $document
 * @testFunction testXmlNodeList
 */
class XmlNodeList implements \IteratorAggregate
{

    /**
     * Список значений
     *
     * @var \DOMNodeList
     */
    private ?\DOMNodeList $_data;

    /**
     * Документ
     *
     * @var \DOMDocument
     */
    private ?\DOMDocument $_document;

    /**
     * Конструктор
     *
     * @param \DOMNodeList $nodelist список узлов
     * @param \DOMDocument $dom документ
     */
    public function __construct(\DOMNodeList $nodelist, \DOMDocument $dom)
    {
        $this->_data = $nodelist;
        $this->_document = $dom;
    }

    /**
     * Возвращает итератор для обхода методом foreach
     *
     * @return XmlNodeListIterator
     * @testFunction testXmlNodeListGetIterator
     */
    public function getIterator(): XmlNodeListIterator
    {
        return new XmlNodeListIterator($this);
    }

    /**
     * Возвращает узел по индексу
     *
     * @param int $index
     * @return XmlNode|null
     * @testFunction testXmlNodeListItem
     */
    public function Item(int $index): ?XmlNode
    {
        if ($this->_data->item($index)) {
            return new XmlNode($this->_data->item($index), $this->_document);
        }
        return null;
    }

    /**
     * Геттер
     *
     * @param string $property
     * @return mixed
     */
    public function __get(string $property): mixed
    {
        if (strtolower($property) == 'document') {
            return $this->_document;
        }
        return null;
    }

    /**
     * Возвращает количество узлов
     *
     * @return int
     * @testFunction testXmlNodeListCount
     */
    public function Count(): int
    {
        return $this->_data->length;
    }

    /**
     * Возвращает первый узел
     *
     * @return XmlNode
     * @testFunction testXmlNodeListFirst
     */
    public function First(): XmlNode
    {
        return $this->Item(0);
    }

    /**
     * Возвращает последний узел
     *
     * @return XmlNode
     * @testFunction testXmlNodeListLast
     */
    public function Last(): XmlNode
    {
        return $this->Item($this->Count() - 1);
    }

    /**
     * Удаляет все узлы в коллекции
     *
     * @return void
     * @testFunction testXmlNodeListRemove
     */
    public function Remove(): void
    {
        foreach ($this as $d) {
            $d->Remove();
        }
    }

    /**
     * Возвращает все узлы в коллекции в виде обьекта
     *
     * @param array $exclude список названий атрибутов и узлов, которые нужно исключить
     * @param int|null $levels количество дочерних узлов
     * @return array|null
     * @testFunction testXmlNodeListToObject
     */
    public function ToObject(array $exclude = array(), ?int $levels = null)
    {
        $ret = array();

        foreach ($this as $child) {
            if (in_array($child->name, $exclude)) {
                continue;
            }
            if (!isset($ret[$child->name])) {
                $ret[$child->name] = [];
            }
            $ret[$child->name][] = $child->ToObject($exclude, $levels);
        }

        foreach ($this as $child) {
            if (count($ret[$child->name]) == 1) {
                $ret[$child->name] = $ret[$child->name][0];
            }
        }

        if (!count($ret)) {
            return null;
        }

        return count($ret) == 1 ? $ret[array_keys($ret)[0]] : $ret;
    }
}
