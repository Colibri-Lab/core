<?php

use PHPUnit\Framework\TestCase;
use Colibri\Common\ObjectHelper;

class ObjectHelperTest extends TestCase
{
    public function testArrayToObject()
    {
        $array = ['key' => 'value'];
        $object = ObjectHelper::ArrayToObject($array);
        $this->assertEquals('value', $object->key);
    }
}
