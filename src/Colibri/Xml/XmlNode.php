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

use Colibri\AppException;
use Colibri\Common\VariableHelper;
use Colibri\IO\FileSystem\File;
use Colibri\Utils\Debug;
use Colibri\Xml\Definitions\XsdSchemaDefinition;
use Colibri\Xml\Serialization\XmlCData;
use Colibri\Xml\Serialization\XmlSerialized;
use Exception;

/**
 * Класс работы с XML объектом
 * 
 * @property-read string $type
 * @property string $value
 * @property-read string $name
 * @property-read string $data
 * @property-read string $encoding
 * @property-read XmlNodeAttributeList $attributes
 * @property-read XmlNode $root
 * @property-read XmlNode $parent
 * @property-read XmlNodeList $nodes
 * @property-read XmlNode $firstChild
 * @property-read XmlNodeList $elements
 * @property-read XmlNodeList $children
 * @property-read XmlNodeList $texts
 * @property \DOMDocument $document
 * @property \DOMNode $raw
 * @property-read string $xml
 * @property-read string $innerXml
 * @property-read string $html
 * @property-read string $innerHtml
 * @property-read XmlNode $next
 * @property-read XmlNode $prev
 * @property-write string $cdata
 * @property-read int $elementsCount количество дочерних элементов
 * 
 * @testFunction testXmlNode
 */
class XmlNode
{

    /**
     * Raw обьект документа
     *
     * @var \DOMDocument
     */
    private ?\DOMDocument $_document;

    /**
     * Raw обьект элемента
     *
     * @var \DOMNode
     */
    private ?\DOMNode $_node;

    /**
     * Конструктор
     *
     * @param \DOMNode $node Узел
     * @param \DOMDocument $dom Документ
     */
    public function __construct(\DOMNode $node, ?\DOMDocument $dom = null)
    {
        $this->_node = $node;
        $this->_document = $dom;
    }

    /**
     * Создает обьект XmlNode из строки или файла
     *
     * @param string $xmlFile Файл или строка xml
     * @param boolean $isFile Файл/Строка
     * @return XmlNode
     * @testFunction testXmlNodeLoad
     */
    public static function Load(string $xmlFile, bool $isFile = true): XmlNode
    {
        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        if (!$isFile) {
            try {
                if (!$xmlFile) {
                    // если пустой
                    throw new AppException('Empty xml string');
                }
                $dom->loadXML($xmlFile);
            }
            catch (\Throwable $e) {
                throw new AppException('Error in file ' . $xmlFile . ': ' . $e->getMessage());
            }
        }
        else {
            if (File::Exists($xmlFile)) {
                try {
                    $dom->load($xmlFile);
                }
                catch (\Throwable $e) {
                    throw new AppException('Error in ' . $xmlFile . ': ' . $e->getMessage());
                }
            }
            else {
                throw new AppException('File ' . $xmlFile . ' does not exists');
            }
        }

        return new XmlNode($dom->documentElement, $dom);
    }

    /**
     *  Создает XmlNode из неполного документа
     *
     * @param string $xmlString Строка xml  
     * @param string $encoding Кодировка строки
     * @return XmlNode
     * @testFunction testXmlNodeLoadNode
     */
    public static function LoadNode(string $xmlString, string $encoding = 'utf-8'): XmlNode
    {
        try {
            $dom = new \DOMDocument('1.0', $encoding);
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->loadXML((strstr($xmlString, '<' . '?xml') === false ? '<' . '?xml version="1.0" encoding="' . $encoding . '"?' . '>' : '') . $xmlString);
            return new XmlNode($dom->documentElement, $dom);
        }
        catch (\Throwable $e) {
            throw new AppException('Error in xml data ' . ((strstr($xmlString, '<' . '?xml') === false ? '<' . '?xml version="1.0" encoding="' . $encoding . '"?' . '>' : '') . $xmlString) . ': ' . $e->getMessage());
        }
    }

    /**
     *  Создает XMLHtmlNode из неполного документа
     *
     * @param string $xmlString Строка html
     * @param string $encoding Кодировка строки
     * @return XmlNode
     * @testFunction testXmlNodeLoadHtmlNode
     */
    public static function LoadHtmlNode(string $xmlString, string $encoding = 'utf-8'): XmlNode
    {
        try {
            $dom = new \DOMDocument('1.0', $encoding);
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->loadHTML((strstr($xmlString, '<' . '?xml') === false ? '<' . '?xml version="1.0" encoding="' . $encoding . '"?' . '>' : '') . $xmlString);
            return new XmlNode($dom->documentElement, $dom);
        }
        catch (\Throwable $e) {
            throw new AppException('Error in xml data ' . ((strstr($xmlString, '<' . '?xml') === false ? '<' . '?xml version="1.0" encoding="' . $encoding . '"?' . '>' : '') . $xmlString) . ': ' . $e->getMessage());
        }
    }

    /**
     * Создает обьект XmlNode из строки или файла html
     *
     * @param string $htmlFile Файл или строка html для загрузки
     * @param boolean $isFile Файл/Не файл
     * @param string $encoding кодировка файла или строки
     * @return XmlNode
     */
    /**
     * @testFunction testXmlNodeLoadHTML
     */
    public static function LoadHTML(string $htmlFile, bool $isFile = true, string $encoding = 'utf-8'): XmlNode
    {
        libxml_use_internal_errors(true);

        $dom = new \DOMDocument('1.0', $encoding);
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        if (!$isFile) {
            try {
                $dom->loadHTML($htmlFile);
            }
            catch (\Throwable $e) {
                throw new AppException('Error in file ' . $htmlFile . ': ' . $e->getMessage());
            }
        }
        else {
            if (File::Exists($htmlFile)) {
                try {
                    $dom->loadHTMLFile($htmlFile);
                }
                catch (\Throwable $e) {
                    throw new AppException('Error in ' . $htmlFile . ': ' . $e->getMessage());
                }
            }
            else {
                throw new AppException('File ' . $htmlFile . ' does not exists');
            }
        }

        return new XmlNode($dom->documentElement, $dom);
    }

    /**
     * Сохраняет в файл или возвращает строку XML хранящуюся в обьекте
     *
     * @param string $filename Путь к файлу для сохранения, если не указано то вернется строка xml
     * @return string|null
     * @testFunction testXmlNodeSave
     */
    public function Save(string $filename = ""): ?string
    {
        if (!empty($filename)) {
            if (!File::Exists($filename)) {
                File::Create($filename);
            }
            $this->_document->formatOutput = true;
            $this->_document->save($filename, LIBXML_NOEMPTYTAG);
            return null;
        }
        else {
            return $this->_document->saveXML(null, LIBXML_NOEMPTYTAG);
        }
    }

    /**
     * Сохраняет в файл или возвращает строку HTML хранящуюся в обьекте
     *
     * @param string $filename Путь к файлу для сохранения, если не указано то вернется строка html
     * @return string|null
     * @testFunction testXmlNodeSaveHTML
     */
    public function SaveHTML(string $filename = ""): ?string
    {
        if (!empty($filename)) {
            $this->_document->saveHTMLFile($filename);
            return null;
        }
        else {
            return $this->_document->saveHTML();
        }
    }

    /**
     * Getter
     *
     * @param string $property запрашиваемое свойство
     * @return mixed
     * @testFunction testXmlNode__get
     */
    public function __get(string $property): mixed
    {
        switch (strtolower($property)) {
            case 'type': {
                    return $this->_node->nodeType;
                }
            case 'value': {
                    return $this->_node->nodeValue;
                }
            case 'iscdata': {
                    return $this->_node->firstChild instanceof \DOMCdataSection;
                }
            case 'name': {
                    return $this->_node->nodeName;
                }
            case 'data': {
                    return $this->_node->data;
                }
            case 'encoding': {
                    return $this->_document->encoding ? $this->_document->encoding : 'utf-8';
                }
            case 'attributes': {
                    if (!is_null($this->_node->attributes)) {
                        return new XmlNodeAttributeList($this->_document, $this->_node, $this->_node->attributes);
                    }
                    else {
                        return null;
                    }
                }
            case 'root': {
                    return $this->_document ? new XmlNode($this->_document->documentElement, $this->_document) : null;
                }
            case 'parent': {
                    return $this->_node->parentNode ? new XmlNode($this->_node->parentNode, $this->_document) : null;
                }
            case 'nodes': {
                    if ($this->_node->childNodes) {
                        return new XmlNodeList($this->_node->childNodes, $this->_document);
                    }
                    else {
                        return null;
                    }
                }
            case 'firstchild': {
                    return $this->_node->firstChild ? new XmlNode($this->_node->firstChild, $this->_document) : null;
                }
            case 'elements': {
                    return $this->Query('./child::*', true);
                }
            case 'children': {
                    return $this->Query('./child::*');
                }
            case 'texts': {
                    return $this->Query('./child::text()');
                }
            case 'elementscount': {
                    $xp = new \DOMXPath($this->_document);
                    return $xp->evaluate('count(./child::*)', $this->_node);
                }
            case 'index': {
                    $xp = new \DOMXPath($this->_document);
                    return $xp->evaluate('count(preceding-sibling::*)', $this->_node);
                }
            case 'document': {
                    return $this->_document;
                }
            case 'raw': {
                    return $this->_node;
                }
            case 'xml': {
                    return $this->_document->saveXML($this->_node, LIBXML_NOEMPTYTAG);
                }
            case 'innerxml': {
                    $data = $this->_document->saveXML($this->_node, LIBXML_NOEMPTYTAG);
                    $data = preg_replace('/<' . $this->name . '.*>/im', '', $data);
                    return preg_replace('/<\/' . $this->name . '.*>/im', '', $data);
                }
            case 'html': {
                    return $this->_document->saveHTML($this->_node);
                }
            case 'innerhtml': {
                    $data = $this->_document->saveHTML($this->_node);
                    $data = preg_replace('/<' . $this->name . '.*>/im', '', $data);
                    return preg_replace('/<\/' . $this->name . '.*>/im', '', $data);
                }
            case 'next': {
                    return $this->_node->nextSibling ? new XmlNode($this->_node->nextSibling, $this->_document) : null;
                }
            case 'prev': {
                    return $this->_node->previousSibling ? new XmlNode($this->_node->previousSibling, $this->_document) : null;
                }
            default: {
                    $item = $this->Item($property);
                    if (is_null($item)) {
                        $items = $this->getElementsByName($property);
                        if ($items->Count() > 0) {
                            $item = $items->First();
                        }
                        else {
                            if ($this->type == 1) {
                                $item = $this->attributes->$property;
                            }
                        }
                    }
                    return $item;
                }
        }
    }

    /**
     * Возвращает путь исходя из запроса
     * @param string $query - запрос к каждому паренту для возвращения данных 
     * @return string
     */
    public function Path(string $query): string
    {

        $ret = [];
        $parents = $this->Query('./ancestor-or-self::*');
        foreach ($parents as $parent) {
            $queryNode = $parent->Query($query);
            if ($queryNode->Count() > 0) {
                $ret[] = $queryNode->First()->value;
            }
        }

        return implode('/', $ret);
    }

    /**
     * Setter
     *
     * @param string $property сохраняемое свойство
     * @param string $value значение свойства
     * @return void
     * @testFunction testXmlNode__set
     */
    public function __set(string $property, mixed $value): void
    {
        switch (strtolower($property)) {
            case 'value': {
                    $this->_node->nodeValue = $value;
                    break;
                }
            case 'cdata': {
                    $this->_node->appendChild($this->_document->createCDATASection($value));
                    break;
                }
            case 'raw': {
                    $this->_node = $value;
                    break;
                }
            case 'document': {
                    $this->_document = $value;
                    break;
                }
            default: {
                    break;
                }
        }
    }

    /**
     * Возвращает обьект XmlNode соответстующий дочернему обьекту с именем $name
     *
     * @param string $name название дочернего узла
     * @return XmlNode|null
     * @testFunction testXmlNodeItem
     */
    public function Item(string $name): ?XmlNode
    {
        $list = $this->Items($name);
        if ($list->Count() > 0) {
            return $list->First();
        }
        else {
            return null;
        }
    }

    /**
     * Возвращает XmlNodeList с названием тэга $name
     *
     * @param string $name название дочерних узлов
     * @return XmlNodeList
     * @testFunction testXmlNodeItems
     */
    public function Items(string $name): XmlNodeList
    {
        return $this->Query('./child::' . $name);
    }

    /**
     * Проверяет является ли текущий узел дочерним к указанному
     *
     * @param XmlNode $node узел для проверки
     * @return boolean
     * @testFunction testXmlNodeIsChildOf
     */
    public function IsChildOf(XmlNode $node): bool
    {
        $p = $this;
        while ($p->parent) {
            if ($p->raw === $node->raw) {
                return true;
            }
            $p = $p->parent;
        }
        return false;
    }

    /**
     * Добавляет заданные узлы/узел в конец
     *
     * @param mixed $nodes узлы для добавки
     * @return void
     * @testFunction testXmlNodeAppend
     */
    public function Append(mixed $nodes): void
    {
        if (VariableHelper::IsNull($nodes)) {
            return;
        }

        if ($nodes instanceof XmlNode) {
            if ($nodes->name == 'html') {
                if ($nodes->body) {
                    $nodes = $nodes->body;
                    if ($nodes->children->Count() > 0) {
                        foreach ($nodes->children as $node) {
                            $node->raw = $this->_document->importNode($node->raw, true);
                            $node->document = $this->_document;
                            $this->_node->appendChild($node->raw);
                        }
                    }
                    else {
                        $nodes->raw = $this->_document->importNode($nodes->raw, true);
                        $nodes->document = $this->_document;
                        $this->_node->appendChild($nodes->raw);
                    }
                }
                else if ($nodes->head) {
                    $nodes = $nodes->head;
                    $nodes->raw = $this->_document->importNode($nodes->raw, true);
                    $nodes->document = $this->_document;
                    $this->_node->appendChild($nodes->raw);
                }
            }
            else {
                $nodes->raw = $this->_document->importNode($nodes->raw, true);
                $nodes->document = $this->_document;
                $this->_node->appendChild($nodes->raw);
            }
        }
        else if ($nodes instanceof XmlNodeList || is_array($nodes)) {
            foreach ($nodes as $node) {

                if ($node->name == 'html') {
                    if ($node->body) {
                        $node = $node->body;
                        if ($node->children->Count() > 0) {
                            foreach ($node->children as $n) {
                                $n->raw = $this->_document->importNode($n->raw, true);
                                $n->document = $this->_document;
                                $this->_node->appendChild($n->raw);
                            }
                        }
                        else {
                            $node->raw = $this->_document->importNode($node->raw, true);
                            $node->document = $this->_document;
                            $this->_node->appendChild($node->raw);
                        }
                    }
                    else if ($node->head) {
                        $node = $node->head;
                        $node->raw = $this->_document->importNode($node->raw, true);
                        $node->document = $this->_document;
                        $this->_node->appendChild($node->raw);
                    }
                }
                else {
                    $node->raw = $this->_document->importNode($node->raw, true);
                    $node->document = $this->_document;
                    $this->_node->appendChild($node->raw);
                }
            }
        }
    }

    /**
     * Добавляет заданные узлы/узел в перед узлом $relation
     *
     * @param mixed $nodes узлу для добавки
     * @param XmlNode $relation перед каким узлом добавить
     * @return void
     * @testFunction testXmlNodeInsert
     */
    public function Insert(mixed $nodes, XmlNode $relation): void
    {
        if ($nodes instanceof XmlNode) {
            $nodes->raw = $this->_document->importNode($nodes->raw, true);
            $nodes->document = $this->_document;
            $this->_node->insertBefore($nodes->raw, $relation->raw);
        }
        else if ($nodes instanceof XmlNodeList) {
            foreach ($nodes as $node) {
                $node->raw = $this->_document->importNode($node->raw, true);
                $node->document = $this->_document;
                $this->_node->insertBefore($node->raw, $relation->raw);
            }
        }
    }

    /**
     * Удаляет текущий узел
     *
     * @return void
     * @testFunction testXmlNodeRemove
     */
    public function Remove(): void
    {
        $this->_node->parentNode->removeChild($this->_node);
    }

    /**
     * Заменяет текущий узел на заданный
     *
     * @param XmlNode $node узел для замены
     * @return void 
     * @testFunction testXmlNodeReplaceTo
     */
    public function ReplaceTo(XmlNode $node): void
    {
        $__node = $node->raw;
        $__node = $this->_document->importNode($__node, true);
        $this->_node->parentNode->replaceChild($__node, $this->_node);
        $this->_node = $__node;
    }

    /**
     * Возвращает элементы с атрибутом @name содержащим указанное имя
     *
     * @param string $name наименование атрибута
     * @return XmlNamedNodeList список узлов
     * @testFunction testXmlNodeGetElementsByName
     */
    public function getElementsByName(string $name): XmlNamedNodeList
    {
        return $this->Query('./child::*[@name="' . $name . '"]', true);
    }

    /**
     * Выполняет XPath запрос
     *
     * @param string $query строка XPath
     * @param bool $returnAsNamedMap вернуть в виде именованого обьекта, в такон обьекте не может быть 2 тэгов с одним именем
     * @return XmlNodeList|XmlNamedNodeList
     * @testFunction testXmlNodeQuery
     */
    public function Query(string $query, bool $returnAsNamedMap = false): XmlNodeList|XmlNamedNodeList
    {
        $xq = new XmlQuery($this, $returnAsNamedMap);
        return $xq->Query($query);
    }

    /**
     * Превращает текущий узел и его дочерние в обьект XmlSerialized
     *
     * @param array $exclude массив названий атрибутов и узлов, которые нужно исключить
     * @param int|null $levels количество вложений, которые нужно выгрузить
     * @return XmlSerialized|XmlCData|string|null
     * @testFunction testXmlNodeToObject
     */
    public function ToObject(array $exclude = array(), ?int $levels = null): XmlSerialized|XmlCData|string|null
    {

        if ($exclude == null) {
            $exclude = [];
        }

        if ($this->attributes->Count() == 0 && $this->children->Count() == 0) {
            if ($this->isCData) {
                return new XmlCData($this->value);
            }
            else {
                return $this->value;
            }
        }

        $attributes = array();
        $content = array();

        foreach ($this->attributes as $attr) {
            $excluded = false;
            if (is_array($exclude)) {
                $excluded = in_array($attr->name, $exclude);
            }
            else if (is_callable($exclude)) {
                $excluded = $exclude($this, $attr);
            }
            if (!$excluded) {
                $attributes[$attr->name] = $attr->value;
            }
        }

        if ($this->children->Count() == 0) {
            if ($this->isCData) {
                $content = new XmlCData($this->value);
            }
            else {
                $content = $this->value;
            }
        }
        else {
            $content = [];
            if (is_null($levels) || $levels > 0) {
                $children = $this->children;
                foreach ($children as $child) {

                    $excluded = false;
                    if (is_array($exclude)) {
                        $excluded = in_array($child->name, $exclude);
                    }
                    else if (is_callable($exclude)) {
                        $excluded = $exclude($this, $child);
                    }

                    if ($excluded) {
                        continue;
                    }

                    $content[] = (object)[$child->name => $child->ToObject($exclude, is_null($levels) ? null : $levels - 1)];
                }
            }
        }

        if (is_array($content)) {
            $retContent = [];
            foreach ($content as $item) {
                $itemArray = get_object_vars($item);
                $key = array_keys($itemArray)[0];
                if (!isset($retContent[$key])) {
                    $retContent[$key] = [];
                }
                $retContent[$key][] = $item->$key;
            }

            foreach ($retContent as $key => $value) {
                if (count($value) == 1) {
                    $retContent[$key] = reset($value);
                }
            }

            $content = $retContent;
        }

        if (!count($attributes) && is_array($content) && !count($content)) {
            return null;
        }

        return new XmlSerialized($this->name, $attributes, $content);
    }

    /**
     * Восстанавливает узел из обьекта XmlSerialized
     *
     * @param XmlSerialized $xmlSerializedObject обьект для восстановления
     * @param mixed|null $elementDefinition описание элементов, на которые нужно ориентироваться
     * @return XmlNode
     * @testFunction testXmlNodeFromObject
     */
    public static function FromObject(XmlSerialized $xmlSerializedObject, mixed $elementDefinition = null): XmlNode
    {

        $xml = XmlNode::LoadNode('<' . $xmlSerializedObject->name . ' />', 'utf-8');
        foreach ($xmlSerializedObject->attributes as $name => $value) {

            $attributeDefinition = $elementDefinition && isset($elementDefinition->attributes[$name]) ? $elementDefinition->attributes[$name] : null;

            if ($attributeDefinition) {
                $baseType = $attributeDefinition->type->restrictions->base;
                // есть специфика только, если это булево значение
                if ($baseType == 'boolean') {
                    $value = $value ? 'true' : 'false';
                }
            }

            $xml->attributes->Append($name, $value);
        }

        if ($xmlSerializedObject->content) {
            if ($xmlSerializedObject->content instanceof XmlCData) {
                $xml->cdata = $xmlSerializedObject->content->value;
            }
            else {
                foreach ($xmlSerializedObject->content as $name => $element) {
                    if (!is_array($element)) {
                        $element = [$element];
                    }
                    foreach ($element as $el) {
                        if (is_string($el)) {
                            $xml->Append(XmlNode::LoadNode('<' . $name . '>' . $el . '</' . $name . '>'));
                        }
                        elseif ($el instanceof XmlCData) {
                            $xml->Append(XmlNode::LoadNode('<' . $name . '><![CDATA[' . $el->value . ']]></' . $name . '>'));
                        }
                        elseif ($el instanceof XmlSerialized && isset($elementDefinition->elements[$name])) {
                            $xml->Append(XmlNode::FromObject($el, $elementDefinition->elements[$name]));
                        }
                    }
                }
            }
        }


        return $xml;
    }
}
