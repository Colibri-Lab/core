<?php

use PHPUnit\Framework\TestCase;
use Colibri\Web\RequestFileCollection;

class RequestFileCollectionTest extends TestCase
{
    public function testItem()
    {
        $data = [
            'file1' => [
                'name' => 'test.txt',
                'type' => 'text/plain',
                'tmp_name' => '/tmp/phpYzdqkD',
                'error' => 0,
                'size' => 123
            ]
        ];
        $collection = new RequestFileCollection($data);
        $file = $collection->Item('file1');
        $this->assertEquals('test.txt', $file->name);
    }

    public function testItemAt()
    {
        $data = [
            'file1' => [
                'name' => 'test.txt',
                'type' => 'text/plain',
                'tmp_name' => '/tmp/phpYzdqkD',
                'error' => 0,
                'size' => 123
            ]
        ];
        $collection = new RequestFileCollection($data);
        $file = $collection->ItemAt(0);
        $this->assertEquals('test.txt', $file->name);
    }
}
