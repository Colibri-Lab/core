<?php

use PHPUnit\Framework\TestCase;
use Colibri\Xml\Definitions\XsdElementDefinition;
use Colibri\Xml\XmlNode;

class XsdElementDefinitionTest extends TestCase
{
    public function testGetName()
    {
        $xmlNode = $this->createMock(XmlNode::class);
        $xmlNode->attributes = (object) ['name' => (object) ['value' => 'testName']];
        $definition = new XsdElementDefinition($xmlNode, null);
        $this->assertEquals('testName', $definition->name);
    }

    public function testGetAnnotation()
    {
        $xmlNode = $this->createMock(XmlNode::class);
        $xmlNode->method('Item')->willReturn((object) ['value' => 'testAnnotation']);
        $definition = new XsdElementDefinition($xmlNode, null);
        $this->assertEquals('testAnnotation', $definition->annotation);
    }

    public function testGetAttributes()
    {
        $xmlNode = $this->createMock(XmlNode::class);
        $xmlNode->method('Query')->willReturn([]);
        $definition = new XsdElementDefinition($xmlNode, null);
        $this->assertIsArray($definition->attributes);
    }

    public function testGetElements()
    {
        $xmlNode = $this->createMock(XmlNode::class);
        $xmlNode->method('Item')->willReturn((object) ['Query' => []]);
        $definition = new XsdElementDefinition($xmlNode, null);
        $this->assertIsArray($definition->elements);
    }

    public function testJsonSerialize()
    {
        $xmlNode = $this->createMock(XmlNode::class);
        $definition = new XsdElementDefinition($xmlNode, null);
        $this->assertIsObject($definition->jsonSerialize());
    }
}
