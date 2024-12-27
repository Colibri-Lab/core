<?php

use PHPUnit\Framework\TestCase;
use Colibri\Data\SqlClient\IDataReader;

class IDataReaderTest extends TestCase
{
    public function testFields()
    {
        $mock = $this->createMock(IDataReader::class);
        $mock->method('Fields')->willReturn(['field1', 'field2']);

        $this->assertEquals(['field1', 'field2'], $mock->Fields());
    }

    public function testRead()
    {
        $mock = $this->createMock(IDataReader::class);
        $mock->method('Read')->willReturn((object)['field1' => 'value1', 'field2' => 'value2']);

        $this->assertEquals((object)['field1' => 'value1', 'field2' => 'value2'], $mock->Read());
    }

    public function testClose()
    {
        $mock = $this->createMock(IDataReader::class);
        $mock->expects($this->once())->method('Close');

        $mock->Close();
    }

    public function testCount()
    {
        $mock = $this->createMock(IDataReader::class);
        $mock->method('Count')->willReturn(2);

        $this->assertEquals(2, $mock->Count());
    }
}

