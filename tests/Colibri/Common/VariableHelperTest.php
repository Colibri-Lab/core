<?php

use PHPUnit\Framework\TestCase;
use Colibri\Common\VariableHelper;

class VariableHelperTest extends TestCase
{
    public function testIsEmpty()
    {
        $this->assertTrue(VariableHelper::IsEmpty(null));
        $this->assertTrue(VariableHelper::IsEmpty(''));
        $this->assertFalse(VariableHelper::IsEmpty('test'));
    }

    public function testIsAssociativeArray()
    {
        $this->assertTrue(VariableHelper::IsAssociativeArray(['key' => 'value']));
        $this->assertFalse(VariableHelper::IsAssociativeArray(['value1', 'value2']));
    }

    public function testArrayToObject()
    {
        $array = ['key' => 'value'];
        $object = VariableHelper::ArrayToObject($array);
        $this->assertEquals('value', $object->key);
    }
}
