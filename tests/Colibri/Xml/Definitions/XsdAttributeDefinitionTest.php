<?php

use PHPUnit\Framework\TestCase;
use Colibri\Xml\Definitions\XsdAttributeDefinition;
use Colibri\Xml\XmlNode;

class XsdAttributeDefinitionTest extends TestCase
{
    public function testGetName()
    {
        $xmlNode = $this->createMock(XmlNode::class);
        $xmlNode->attributes = (object) ['name' => (object) ['value' => 'testName']];
        $definition = new XsdAttributeDefinition($xmlNode, null);
        $this->assertEquals('testName', $definition->name);
    }

    public function testGetAnnotation()
    {
        $xmlNode = $this->createMock(XmlNode::class);
        $xmlNode->method('Item')->willReturn((object) ['value' => 'testAnnotation']);
        $definition = new XsdAttributeDefinition($xmlNode, null);
        $this->assertEquals('testAnnotation', $definition->annotation);
    }

    public function testGetType()
    {
        $xmlNode = $this->createMock(XmlNode::class);
        $xmlNode->attributes = (object) ['type' => (object) ['value' => 'xs:string']];
        $definition = new XsdAttributeDefinition($xmlNode, null);
        $this->assertEquals('string', $definition->type->name);
    }

    public function testJsonSerialize()
    {
        $xmlNode = $this->createMock(XmlNode::class);
        $definition = new XsdAttributeDefinition($xmlNode, null);
        $this->assertIsObject($definition->jsonSerialize());
    }
}
