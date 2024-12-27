<?php

use PHPUnit\Framework\TestCase;
use Colibri\Xml\Serialization\XmlSerialized;

class XmlSerializedTest extends TestCase
{
    public function testConstructor()
    {
        $name = 'test';
        $attributes = ['attr1' => 'value1'];
        $content = ['key' => 'value'];

        $xmlSerialized = new XmlSerialized($name, $attributes, $content);

        $this->assertEquals($name, $xmlSerialized->name);
        $this->assertEquals((object)$attributes, $xmlSerialized->attributes);
        $this->assertEquals($content, $xmlSerialized->content);
    }

    public function testJsonSerialize()
    {
        $name = 'test';
        $attributes = ['attr1' => 'value1'];
        $content = ['key' => 'value'];

        $xmlSerialized = new XmlSerialized($name, $attributes, $content);
        $json = json_encode($xmlSerialized);

        $expectedJson = json_encode([
            'class' => XmlSerialized::class,
            'name' => $name,
            'content' => $content,
            'attributes' => (object)$attributes
        ]);

        $this->assertJsonStringEqualsJsonString($expectedJson, $json);
    }

    public function testJsonUnserialize()
    {
        $json = json_encode([
            'class' => XmlSerialized::class,
            'name' => 'test',
            'content' => ['key' => 'value'],
            'attributes' => ['attr1' => 'value1']
        ]);

        $xmlSerialized = XmlSerialized::jsonUnserialize($json);

        $this->assertInstanceOf(XmlSerialized::class, $xmlSerialized);
        $this->assertEquals('test', $xmlSerialized->name);
        $this->assertEquals((object)['attr1' => 'value1'], $xmlSerialized->attributes);
        $this->assertEquals(['key' => 'value'], $xmlSerialized->content);
    }
}
