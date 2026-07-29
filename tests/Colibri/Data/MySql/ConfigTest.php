<?php

use PHPUnit\Framework\TestCase;
use Colibri\Data\MySql\Config;
use Colibri\Data\SqlClient\IConfig;

class MySqlConfigTest extends TestCase
{
    public function testImplementsIConfig(): void
    {
        $this->assertInstanceOf(IConfig::class, new Config());
    }

    public function testDbmsType(): void
    {
        $this->assertEquals('relational', Config::DbmsType());
    }

    public function testAllowedTypes(): void
    {
        $types = Config::AllowedTypes();
        $this->assertIsArray($types);
        $this->assertArrayHasKey('int', $types);
        $this->assertArrayHasKey('varchar', $types);
        $this->assertArrayHasKey('text', $types);
    }

    public function testHasIndexes(): void
    {
        $this->assertTrue(Config::HasIndexes());
    }

    public function testFieldsHasPrefix(): void
    {
        $this->assertIsBool(Config::FieldsHasPrefix());
    }

    public function testHasMultiFieldIndexes(): void
    {
        $this->assertIsBool(Config::HasMultiFieldIndexes());
    }

    public function testHasVirtual(): void
    {
        $this->assertIsBool(Config::HasVirtual());
    }

    public function testHasAutoincrement(): void
    {
        $this->assertTrue(Config::HasAutoincrement());
    }

    public function testIndexTypes(): void
    {
        $types = Config::IndexTypes();
        $this->assertIsArray($types);
    }

    public function testIndexMethods(): void
    {
        $methods = Config::IndexMethods();
        $this->assertIsArray($methods);
    }

    public function testSymbol(): void
    {
        $symbol = Config::Symbol();
        $this->assertIsString($symbol);
        $this->assertNotEmpty($symbol);
    }

    public function testJsonIndexes(): void
    {
        $this->assertIsBool(Config::JsonIndexes());
    }

    public function testHasTriggers(): void
    {
        $this->assertIsBool(Config::HasTriggers());
    }
}
