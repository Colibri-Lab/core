<?php

use PHPUnit\Framework\TestCase;
use Colibri\Data\PgSql\Config;
use Colibri\Data\SqlClient\IConfig;

class PgSqlConfigTest extends TestCase
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
        $this->assertNotEmpty($types);
    }

    public function testHasIndexes(): void
    {
        $this->assertIsBool(Config::HasIndexes());
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
        $this->assertIsBool(Config::HasAutoincrement());
    }

    public function testIndexTypes(): void
    {
        $this->assertIsArray(Config::IndexTypes());
    }

    public function testIndexMethods(): void
    {
        $this->assertIsArray(Config::IndexMethods());
    }

    public function testSymbol(): void
    {
        $this->assertIsString(Config::Symbol());
    }

    public function testJsonIndexes(): void
    {
        $this->assertIsBool(Config::JsonIndexes());
    }
}
