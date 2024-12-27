<?php

use PHPUnit\Framework\TestCase;
use Colibri\Utils\ExtendedObject;

class ExtendedObjectTest extends TestCase
{
    public function testSetData()
    {
        $object = new ExtendedObject();
        $data = ['key' => 'value'];
        $object->SetData($data);
        $this->assertEquals($data, $object->GetData(false));
    }

    public function testToArray()
    {
        $data = ['key' => 'value'];
        $object = new ExtendedObject($data);
        $this->assertEquals($data, $object->ToArray());
    }

    public function testValidate()
    {
        $data = ['key' => 'value'];
        $object = new ExtendedObject($data);
        $this->assertTrue($object->Validate());
    }

    public function testIsChanged()
    {
        $object = new ExtendedObject();
        $this->assertFalse($object->IsChanged());
        $object->SetData(['key' => 'value']);
        $this->assertTrue($object->IsChanged());
    }

    public function testJsonSerialize()
    {
        $data = ['key' => 'value'];
        $object = new ExtendedObject($data);
        $this->assertEquals(json_encode($data), json_encode($object));
    }
}
