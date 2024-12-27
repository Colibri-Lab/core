<?php

use PHPUnit\Framework\TestCase;
use Colibri\Xml\Definitions\XsdSimpleTypeDefinition;
use Colibri\Xml\XmlNode;

class XsdSimpleTypeDefinitionTest extends TestCase
{
    public function testGetName()
    {
        $xmlNode = $this->createMock(XmlNode::class);
        $xmlNode->attributes = (object) ['name' => (object) ['value' => 'testName']];
        $definition = new XsdSimpleTypeDefinition($xmlNode);
        $this->assertEquals('testName', $definition->name);
    }

    public function testGetAnnotation()
    {
        $xmlNode = $this->createMock(XmlNode::class);
        $xmlNode->method('Query')->willReturn([(object) ['value' => 'testAnnotation']]);
        $definition = new XsdSimpleTypeDefinition($xmlNode);
        $this->assertEquals('testAnnotation', $definition->annotation);
    }

    public function testGetRestrictions()
    {
        $xmlNode = $this->createMock(XmlNode::class);
        $restrictionNode = $this->createMock(XmlNode::class);
        $restrictionNode->attributes = (object) ['base' => (object) ['value' => 'xs:string']];
        $xmlNode->method('Item')->willReturn($restrictionNode);
        $definition = new XsdSimpleTypeDefinition($xmlNode);
        $this->assertEquals('string', $definition->restrictions->base);
    }

    public function testGetAttributes()
    {
        $xmlNode = $this->createMock(XmlNode::class);
        $xmlNode->method('Query')->willReturn([]);
        $definition = new XsdSimpleTypeDefinition($xmlNode);
        $this->assertIsArray($definition->attributes);
    }

    public function testJsonSerialize()
    {
        $xmlNode = $this->createMock(XmlNode::class);
        $definition = new XsdSimpleTypeDefinition($xmlNode);
        $this->assertIsObject($definition->jsonSerialize());
    }
}
