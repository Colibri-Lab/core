<?php

use PHPUnit\Framework\TestCase;
use Colibri\Web\PayloadCopy;
use Colibri\Web\Request;

class PayloadCopyTest extends TestCase
{
    public function testOffsetSet()
    {
        $payload = new PayloadCopy(Request::PAYLOAD_TYPE_JSON);
        $payload['key'] = 'value';
        $this->assertEquals('value', $payload['key']);
    }

    public function testOffsetExists()
    {
        $payload = new PayloadCopy(Request::PAYLOAD_TYPE_JSON);
        $payload['key'] = 'value';
        $this->assertTrue(isset($payload['key']));
    }

    public function testOffsetUnset()
    {
        $payload = new PayloadCopy(Request::PAYLOAD_TYPE_JSON);
        $payload['key'] = 'value';
        unset($payload['key']);
        $this->assertFalse(isset($payload['key']));
    }

    public function testOffsetGet()
    {
        $payload = new PayloadCopy(Request::PAYLOAD_TYPE_JSON);
        $payload['key'] = 'value';
        $this->assertEquals('value', $payload['key']);
    }

    public function testCount()
    {
        $payload = new PayloadCopy(Request::PAYLOAD_TYPE_JSON);
        $payload['key1'] = 'value1';
        $payload['key2'] = 'value2';
        $this->assertCount(2, $payload);
    }

    public function testToArray()
    {
        $payload = new PayloadCopy(Request::PAYLOAD_TYPE_JSON);
        $payload['key'] = 'value';
        $array = $payload->ToArray();
        $this->assertIsArray($array);
        $this->assertEquals('value', $array['key']);
    }
}
