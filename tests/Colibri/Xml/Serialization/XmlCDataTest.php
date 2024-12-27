<?php

use PHPUnit\Framework\TestCase;
use Colibri\Xml\Serialization\XmlCData;

class XmlCDataTest extends TestCase
{
    public function testConstructor()
    {
        $value = 'test value';
        $xmlCData = new XmlCData($value);

        $this->assertEquals($value, $xmlCData->value);
    }

    public function testJsonSerialize()
    {
        $value = 'test value';
        $xmlCData = new XmlCData($value);
        $json = json_encode($xmlCData);

        $expectedJson = json_encode([
            'class' => XmlCData::class,
            'value' => $value
        ]);

        $this->assertJsonStringEqualsJsonString($expectedJson, $json);
    }
}
