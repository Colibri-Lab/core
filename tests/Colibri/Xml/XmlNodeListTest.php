<?php

namespace Colibri\Tests\Xml;

use Colibri\Xml\XmlNode;
use Colibri\Xml\XmlNodeList;
use PHPUnit\Framework\TestCase;

class XmlNodeListTest extends TestCase
{
    public function testItem()
    {
        $xmlString = '<root><element>value1</element><element>value2</element></root>';
        $xmlNode = XmlNode::LoadNode($xmlString, false);
        $xmlNodeList = $xmlNode->children;

        $this->assertEquals('value1', $xmlNodeList->Item(0)->value);
        $this->assertEquals('value2', $xmlNodeList->Item(1)->value);
    }

    public function testCount()
    {
        $xmlString = '<root><element>value1</element><element>value2</element></root>';
        $xmlNode = XmlNode::LoadNode($xmlString, false);
        $xmlNodeList = $xmlNode->children;

        $this->assertEquals(2, $xmlNodeList->Count());
    }
}
