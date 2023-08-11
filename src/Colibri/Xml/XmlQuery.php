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

use DOMXPath;

/**
 * Класс запросчик к документу
 * @testFunction testXmlQuery
 */
class XmlQuery
{

    /**
     * Узел конекст
     *
     * @var XmlNode
     */
    private ? XmlNode $_contextNode;
    /**
     * Элемент управления запросами
     *
     * @var DOMXPath
     */
    private ? DOMXPath $_operator;
    /**
     * Вернуть в виде именованной коллекции, или в виде простого списка
     *
     * @var bool
     */
    private bool $_returnAsNamedMap;

    /**
     * Конструктор
     *
     * @param XmlNode $node контекстный узел
     * @param boolean $returnAsNamedMap вернуть в виде именованной коллекции
     */
    public function __construct(XmlNode $node, bool $returnAsNamedMap = false, array $namespaces = [])
    {
        $this->_returnAsNamedMap = $returnAsNamedMap;
        $this->_contextNode = $node;
        $this->_operator = new DOMXPath($this->_contextNode->document);
        if(!empty($namespaces)) {
            foreach($namespaces as $prefix => $namespace) {
                $this->_operator->registerNamespace($prefix, $namespace);
            }
        }
    }

    /**
     * Выполняет запрос
     *
     * @param string $xpathQuery строка запроса
     * @return XmlNodeList|XmlNamedNodeList список узлов
     * @testFunction testXmlQueryQuery
     */
    public function Query(string $xpathQuery): XmlNodeList|XmlNamedNodeList
    {
        $res = $this->_operator->query($xpathQuery, $this->_contextNode->raw);
        if (!$res) {
            return new XmlNamedNodeList(new \DOMNodeList(), $this->_contextNode->document);
        }
        if ($this->_returnAsNamedMap) {
            return new XmlNamedNodeList($res, $this->_contextNode->document);
        }
        return new XmlNodeList($res, $this->_contextNode->document);
    }
}