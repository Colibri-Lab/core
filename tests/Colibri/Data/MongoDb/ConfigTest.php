<?php

use PHPUnit\Framework\TestCase;
use Colibri\Data\MongoDb\Config;
use Colibri\Data\SqlClient\IConfig;

class MongoDbConfigTest extends TestCase
{
    public function testImplementsIConfig(): void
    {
        $this->assertInstanceOf(IConfig::class, new Config());
    }

    public function testDbmsType(): void
    {
        $this->assertEquals('nosql', Config::DbmsType());
    }

    public function testAllowedTypes(): void
    {
        $types = Config::AllowedTypes();
        $this->assertIsArray($types);
        $this->assertNotEmpty($types);
    }

    public function testHasIndexes(): void
    {
        $this->assertFalse(Config::HasIndexes());
    }

    public function testFieldsHasPrefix(): void
    {
        $this->assertFalse(Config::FieldsHasPrefix());
    }

    public function testHasMultiFieldIndexes(): void
    {
        $this->assertFalse(Config::HasMultiFieldIndexes());
    }

    public function testHasVirtual(): void
    {
        $this->assertFalse(Config::HasVirtual());
    }

    public function testHasAutoincrement(): void
    {
        $this->assertFalse(Config::HasAutoincrement());
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
        $this->assertFalse(Config::JsonIndexes());
    }

    public function testHasTriggers(): void
    {
        $this->assertFalse(Config::HasTriggers());
    }
}
