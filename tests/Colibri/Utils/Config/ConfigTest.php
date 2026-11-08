<?php

use PHPUnit\Framework\TestCase;
use Colibri\Utils\Config\Config;

class ConfigTest extends TestCase
{
    public function testConstructorWithArray(): void
    {
        $config = new Config(['key' => 'value'], false);
        $this->assertInstanceOf(Config::class, $config);
    }

    public function testQueryReturnsConfig(): void
    {
        $config = new Config(['database' => ['host' => 'localhost', 'port' => 3306]], false);
        $result = $config->Query('database');
        $this->assertInstanceOf(Config::class, $result);
    }

    public function testQueryNestedKey(): void
    {
        $config = new Config(['database' => ['host' => 'localhost']], false);
        $nested = $config->Query('database');
        $host = $nested->Query('host');
        $this->assertEquals('localhost', $host->GetValue());
    }

    public function testGetValue(): void
    {
        $config = new Config(['name' => 'Colibri'], false);
        $value = $config->Query('name')->GetValue();
        $this->assertEquals('Colibri', $value);
    }

    public function testGetValueDefault(): void
    {
        $config = new Config(['name' => 'Colibri'], false);
        $result = $config->Query('nonexistent', 'default');
        $this->assertInstanceOf(Config::class, $result);
    }

    public function testAsArray(): void
    {
        $data = ['key1' => 'val1', 'key2' => 'val2'];
        $config = new Config($data, false);
        $this->assertEquals($data, $config->AsArray());
    }

    public function testToArray(): void
    {
        $data = ['a' => 1, 'b' => 2];
        $config = new Config($data, false);
        $this->assertEquals($data, $config->ToArray());
    }

    public function testAsObject(): void
    {
        $config = new Config(['name' => 'test'], false);
        $obj = $config->AsObject();
        $this->assertIsObject($obj);
    }

    public function testGetFile(): void
    {
        $config = new Config(['key' => 'value'], false);
        $this->assertIsString($config->GetFile());
    }

    public function testSet(): void
    {
        $config = new Config(['key' => 'old'], false);
        $config->Set('key', 'new');
        $this->assertEquals('new', $config->Query('key')->GetValue());
    }

    public function testLoadWithYamlString(): void
    {
        $yaml = "name: TestProject\nversion: 1.0";
        $config = Config::Load($yaml);
        $this->assertInstanceOf(Config::class, $config);
        $this->assertEquals('TestProject', $config->Query('name')->GetValue());
    }

    public function testItem(): void
    {
        $config = new Config([10, 20, 30], false);
        $item = $config->Item(0);
        $this->assertInstanceOf(Config::class, $item);
    }

    public function testIterator(): void
    {
        $data = ['a' => 1, 'b' => 2, 'c' => 3];
        $config = new Config($data, false);
        $count = 0;
        foreach ($config as $item) {
            $count++;
        }
        $this->assertEquals(3, $count);
    }

    public function testIsKindOfObject(): void
    {
        $config = new Config(['nested' => ['a' => 1]], false);
        $this->assertTrue($config->isKindOfObject('nested'));
        $this->assertFalse($config->isKindOfObject('nonexistent'));
    }
}
