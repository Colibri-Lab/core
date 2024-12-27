<?php

use PHPUnit\Framework\TestCase;
use Colibri\Web\RequestCollection;

class RequestCollectionTest extends TestCase
{
    public function testGet()
    {
        $data = ['key' => 'value'];
        $collection = new RequestCollection($data);
        $this->assertEquals('value', $collection->key);
    }

    public function testStripSlashes()
    {
        $data = ['key' => 'value'];
        $collection = new RequestCollection($data, true);
        $this->assertEquals('value', $collection->key);
    }
}
