<?php

use PHPUnit\Framework\TestCase;
use Colibri\Utils\Config\ConfigItemsList;
use Colibri\Utils\Config\Config;

class ConfigItemsListTest extends TestCase
{
    public function testConstructorCreatesInstance(): void
    {
        $list = new ConfigItemsList([1, 2, 3]);
        $this->assertInstanceOf(ConfigItemsList::class, $list);
    }

    public function testItemReturnsConfig(): void
    {
        $list = new ConfigItemsList([['key' => 'value1'], ['key' => 'value2']]);
        $item = $list->Item(0);
        $this->assertInstanceOf(Config::class, $item);
    }

    public function testItemReturnsCorrectValue(): void
    {
        $list = new ConfigItemsList([['name' => 'first'], ['name' => 'second']]);
        $item = $list->Item(1);
        $this->assertEquals('second', $item->Query('name')->GetValue());
    }

    public function testAsArray(): void
    {
        $data = [['a' => 1], ['b' => 2]];
        $list = new ConfigItemsList($data);
        $this->assertEquals($data, $list->AsArray());
    }

    public function testCount(): void
    {
        $list = new ConfigItemsList([1, 2, 3]);
        $this->assertEquals(3, $list->Count());
    }

    public function testEmptyList(): void
    {
        $list = new ConfigItemsList();
        $this->assertEquals(0, $list->Count());
    }

    public function testAdd(): void
    {
        $list = new ConfigItemsList();
        $list->Add(['key' => 'value']);
        $this->assertEquals(1, $list->Count());
    }
}
