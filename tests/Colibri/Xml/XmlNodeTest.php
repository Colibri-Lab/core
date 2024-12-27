<?php

namespace Colibri\Tests\Xml;

use Colibri\Xml\XmlNode;
use PHPUnit\Framework\TestCase;

class XmlNodeTest extends TestCase
{
    public function testLoadNode()
    {
        $xmlString = '<root><element>value</element></root>';
        $xmlNode = XmlNode::LoadNode($xmlString, false);

        $this->assertEquals('root', $xmlNode->name);
        $this->assertEquals('value', $xmlNode->firstChild->value);
    }

    public function testSave()
    {
        $xmlString = '<root><element>value</element></root>';
        $xmlNode = XmlNode::LoadNode($xmlString, false);
        $savedXml = $xmlNode->Save();

        $this->assertStringContainsString('<root><element>value</element></root>', $savedXml);
    }
}
