<?php

namespace Colibri\Tests\Xml;

use Colibri\Xml\XmlNode;
use Colibri\Xml\XmlNamedNodeList;
use PHPUnit\Framework\TestCase;

class XmlNamedNodeListTest extends TestCase
{
    public function testItem()
    {
        $xmlString = '<root><element name="test1">value1</element><element name="test2">value2</element></root>';
        $xmlNode = XmlNode::LoadNode($xmlString, false);
        $xmlNamedNodeList = $xmlNode->getElementsByName('element');

        $this->assertEquals('value1', $xmlNamedNodeList->Item('element')->value);
    }

    public function testCount()
    {
        $xmlString = '<root><element name="test1">value1</element><element name="test2">value2</element></root>';
        $xmlNode = XmlNode::LoadNode($xmlString, false);
        $xmlNamedNodeList = $xmlNode->getElementsByName('element');

        $this->assertEquals(2, $xmlNamedNodeList->Count());
    }
}
