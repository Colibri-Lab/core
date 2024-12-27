<?php

namespace Colibri\Tests\Xml;

use Colibri\Xml\XmlNode;
use Colibri\Xml\XmlQuery;
use PHPUnit\Framework\TestCase;

class XmlQueryTest extends TestCase
{
    public function testQuery()
    {
        $xmlString = '<root><element name="test">value</element></root>';
        $xmlNode = XmlNode::LoadNode($xmlString, false);
        $xmlQuery = new XmlQuery($xmlNode);

        $result = $xmlQuery->Query('//element[@name="test"]');
        $this->assertEquals(1, $result->Count());
        $this->assertEquals('value', $result->First()->value);
    }
}
