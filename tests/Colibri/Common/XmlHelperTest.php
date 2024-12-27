<?php

use PHPUnit\Framework\TestCase;
use Colibri\Common\XmlHelper;
use Colibri\Xml\XmlNode;

class XmlHelperTest extends TestCase
{
    public function testEncode()
    {
        $array = ['key' => 'value'];
        $xml = XmlHelper::Encode($array);
        $this->assertStringContainsString('<key><![CDATA[value]]></key>', $xml);
    }

    public function testDecode()
    {
        $xmlString = '<object><key><![CDATA[value]]></key></object>';
        $xmlNode = XmlHelper::Decode($xmlString);
        $this->assertInstanceOf(XmlNode::class, $xmlNode);
    }

    public function testToObject()
    {
        $xmlString = '<object><key><![CDATA[value]]></key></object>';
        $object = XmlHelper::ToObject($xmlString);
        $this->assertEquals('value', $object->key);
    }
}
