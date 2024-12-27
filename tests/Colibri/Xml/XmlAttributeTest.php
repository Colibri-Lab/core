<?php

namespace Colibri\Tests\Xml;

use Colibri\Xml\XmlNode;
use PHPUnit\Framework\TestCase;

class XmlAttributeTest extends TestCase
{
    public function testGetValue()
    {
        $xmlString = '<root attribute="value"></root>';
        $xmlNode = XmlNode::LoadNode($xmlString, false);
        $attribute = $xmlNode->attributes->attribute;

        $this->assertEquals('value', $attribute->value);
    }

    public function testSetValue()
    {
        $xmlString = '<root attribute="value"></root>';
        $xmlNode = XmlNode::LoadNode($xmlString, false);
        $attribute = $xmlNode->attributes->attribute;
        $attribute->value = 'newValue';

        $this->assertEquals('newValue', $attribute->value);
    }
}
