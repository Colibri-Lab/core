<?php

use PHPUnit\Framework\TestCase;
use Colibri\Data\SqlClient\IConfig;

class IConfigTest extends TestCase
{
    public function testDbmsType()
    {
        $mock = $this->createMock(IConfig::class);
        $mock->method('DbmsType')->willReturn('relational');

        $this->assertEquals('relational', $mock->DbmsType());
    }

    public function testAllowedTypes()
    {
        $mock = $this->createMock(IConfig::class);
        $mock->method('AllowedTypes')->willReturn(['type1', 'type2']);

        $this->assertEquals(['type1', 'type2'], $mock->AllowedTypes());
    }

    public function testHasIndexes()
    {
        $mock = $this->createMock(IConfig::class);
        $mock->method('HasIndexes')->willReturn(true);

        $this->assertTrue($mock->HasIndexes());
    }

    public function testFieldsHasPrefix()
    {
        $mock = $this->createMock(IConfig::class);
        $mock->method('FieldsHasPrefix')->willReturn(true);

        $this->assertTrue($mock->FieldsHasPrefix());
    }

    public function testHasMultiFieldIndexes()
    {
        $mock = $this->createMock(IConfig::class);
        $mock->method('HasMultiFieldIndexes')->willReturn(true);

        $this->assertTrue($mock->HasMultiFieldIndexes());
    }

    public function testHasVirtual()
    {
        $mock = $this->createMock(IConfig::class);
        $mock->method('HasVirtual')->willReturn(true);

        $this->assertTrue($mock->HasVirtual());
    }

    public function testHasAutoincrement()
    {
        $mock = $this->createMock(IConfig::class);
        $mock->method('HasAutoincrement')->willReturn(true);

        $this->assertTrue($mock->HasAutoincrement());
    }

    public function testIndexTypes()
    {
        $mock = $this->createMock(IConfig::class);
        $mock->method('IndexTypes')->willReturn(['type1', 'type2']);

        $this->assertEquals(['type1', 'type2'], $mock->IndexTypes());
    }

    public function testIndexMethods()
    {
        $mock = $this->createMock(IConfig::class);
        $mock->method('IndexMethods')->willReturn(['method1', 'method2']);

        $this->assertEquals(['method1', 'method2'], $mock->IndexMethods());
    }

    public function testSymbol()
    {
        $mock = $this->createMock(IConfig::class);
        $mock->method('Symbol')->willReturn('`');

        $this->assertEquals('`', $mock->Symbol());
    }

    public function testJsonIndexes()
    {
        $mock = $this->createMock(IConfig::class);
        $mock->method('JsonIndexes')->willReturn(true);

        $this->assertTrue($mock->JsonIndexes());
    }
    
}
