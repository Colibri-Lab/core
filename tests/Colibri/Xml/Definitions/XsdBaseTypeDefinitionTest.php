<?php

use PHPUnit\Framework\TestCase;
use Colibri\Xml\Definitions\XsdBaseTypeDefinition;

class XsdBaseTypeDefinitionTest extends TestCase
{
    public function testGetName()
    {
        $definition = new XsdBaseTypeDefinition('xs:string');
        $this->assertEquals('string', $definition->name);
    }

    public function testGetRestrictions()
    {
        $definition = new XsdBaseTypeDefinition('xs:string');
        $this->assertEquals('string', $definition->restrictions->base);
    }

    public function testJsonSerialize()
    {
        $definition = new XsdBaseTypeDefinition('xs:string');
        $this->assertIsObject($definition->jsonSerialize());
    }
}
