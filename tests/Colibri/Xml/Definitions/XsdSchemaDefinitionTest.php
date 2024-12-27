<?php

use PHPUnit\Framework\TestCase;
use Colibri\Xml\Definitions\XsdSchemaDefinition;
use Colibri\Xml\XmlNode;

class XsdSchemaDefinitionTest extends TestCase
{
    public function testLoad()
    {
        $schema = XsdSchemaDefinition::Load('testFile.xml', true);
        $this->assertInstanceOf(XsdSchemaDefinition::class, $schema);
    }

    public function testGetTypes()
    {
        $xmlNode = $this->createMock(XmlNode::class);
        $xmlNode->method('Query')->willReturn([]);
        $schema = new XsdSchemaDefinition('testFile.xml', true);
        $this->assertIsArray($schema->types);
    }

    public function testGetElements()
    {
        $xmlNode = $this->createMock(XmlNode::class);
        $xmlNode->method('Query')->willReturn([]);
        $schema = new XsdSchemaDefinition('testFile.xml', true);
        $this->assertIsArray($schema->elements);
    }

    public function testJsonSerialize()
    {
        $schema = new XsdSchemaDefinition('testFile.xml', true);
        $this->assertIsObject($schema->jsonSerialize());
    }
}
