<?php

use PHPUnit\Framework\TestCase;
use Colibri\IO\Request\Data;
use Colibri\IO\Request\DataItem;

class DataTest extends TestCase
{
    public function testFromArray()
    {
        $array = [
            'key1' => 'value1',
            'key2' => 'value2'
        ];
        $data = Data::FromArray($array);
        $this->assertCount(2, $data);
        $this->assertInstanceOf(DataItem::class, $data[0]);
        $this->assertEquals('key1', $data[0]->name);
        $this->assertEquals('value1', $data[0]->value);
    }
}
