<?php

namespace Colibri\Tests\Xml;

use Colibri\Xml\XmlNode;
use PHPUnit\Framework\TestCase;

class XmlNodeAttributeListTest extends TestCase
{
    public function testAppendAndRemove()
    {
        $xmlString = '<root></root>';
        $xmlNode = XmlNode::LoadNode($xmlString, false);
        $attributes = $xmlNode->attributes;

        $attributes->Append('test', 'value');
        $this->assertEquals('value', $attributes->test->value);

        $attributes->Remove('test');
        $this->assertNull($attributes->test);
    }
}
