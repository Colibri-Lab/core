<?php

use PHPUnit\Framework\TestCase;
use Colibri\IO\Request\DataItem;

class DataItemTest extends TestCase
{
    public function testConstructor()
    {
        $dataItem = new DataItem('name', 'value');
        $this->assertEquals('name', $dataItem->name);
        $this->assertEquals('value', $dataItem->value);
    }
}
