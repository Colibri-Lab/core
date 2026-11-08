<?php

use PHPUnit\Framework\TestCase;
use Colibri\Utils\Menu\Item;

class ItemTest extends TestCase
{
    public function testConstructorCreatesItem(): void
    {
        $item = new Item('home', 'Home', 'Home page');
        $this->assertInstanceOf(Item::class, $item);
    }

    public function testNameProperty(): void
    {
        $item = new Item('dashboard', 'Dashboard', 'Main dashboard');
        $this->assertEquals('dashboard', $item->name);
    }

    public function testTitleProperty(): void
    {
        $item = new Item('profile', 'My Profile', 'User profile');
        $this->assertEquals('My Profile', $item->title);
    }

    public function testDescriptionProperty(): void
    {
        $item = new Item('settings', 'Settings', 'App settings');
        $this->assertEquals('App settings', $item->description);
    }

    public function testIconProperty(): void
    {
        $item = new Item('help', 'Help', 'Help center', 'help-icon');
        $this->assertEquals('help-icon', $item->icon);
    }

    public function testExecuteProperty(): void
    {
        $item = new Item('action', 'Action', 'Do action', '', 'doSomething()');
        $this->assertEquals('doSomething()', $item->execute);
    }

    public function testEnabledDefaultTrue(): void
    {
        $item = new Item('test', 'Test', 'Test item');
        $this->assertTrue($item->enabled);
    }

    public function testChildrenDefaultEmpty(): void
    {
        $item = new Item('parent', 'Parent', 'Parent item');
        $this->assertIsArray($item->children);
        $this->assertEmpty($item->children);
    }

    public function testParentDefaultNull(): void
    {
        $item = new Item('test', 'Test', 'Test item');
        $this->assertNull($item->parent);
    }

    public function testIndexProperty(): void
    {
        $item = new Item('home', 'Home', 'Home page');
        $this->assertEquals('/home/', $item->index);
    }

    public function testUnknownPropertyReturnsNull(): void
    {
        $item = new Item('test', 'Test', 'Test item');
        $this->assertNull($item->unknownProperty);
    }

    public function testCreateStaticMethod(): void
    {
        $item = Item::Create('test', 'Test', 'A test item');
        $this->assertInstanceOf(Item::class, $item);
        $this->assertEquals('test', $item->name);
    }

    public function testFromArrayBasic(): void
    {
        $item = Item::FromArray([
            'name' => 'menu',
            'title' => 'Menu',
            'description' => 'Navigation menu'
        ]);
        $this->assertInstanceOf(Item::class, $item);
        $this->assertEquals('menu', $item->name);
    }

    public function testFromArrayWithChildren(): void
    {
        $item = Item::FromArray([
            'name' => 'parent',
            'title' => 'Parent',
            'description' => 'Parent item',
            'children' => [
                ['name' => 'child1', 'title' => 'Child 1', 'description' => 'First child'],
                ['name' => 'child2', 'title' => 'Child 2', 'description' => 'Second child'],
            ]
        ]);
        $this->assertCount(2, $item->children);
    }

    public function testAddChild(): void
    {
        $parent = new Item('parent', 'Parent', 'Parent');
        $child = new Item('child', 'Child', 'Child item');
        $parent->Add($child);
        $this->assertCount(1, $parent->children);
        $this->assertSame($parent, $child->parent);
    }

    public function testAddMultipleChildren(): void
    {
        $parent = new Item('parent', 'Parent', 'Parent');
        $child1 = new Item('child1', 'Child 1', 'First');
        $child2 = new Item('child2', 'Child 2', 'Second');
        $parent->Add([$child1, $child2]);
        $this->assertCount(2, $parent->children);
    }

    public function testRouteWithNoParent(): void
    {
        $item = new Item('home', 'Home', 'Home page');
        $this->assertEquals('/home/', $item->Route());
    }

    public function testRouteWithParent(): void
    {
        $parent = new Item('admin', 'Admin', 'Admin section');
        $child = new Item('users', 'Users', 'User management');
        $parent->Add($child);
        $this->assertEquals('/admin/users/', $child->Route());
    }

    public function testJsonSerialize(): void
    {
        $item = new Item('test', 'Test', 'Test item');
        $json = json_encode($item);
        $this->assertIsString($json);
        $data = json_decode($json);
        $this->assertEquals('test', $data->name);
        $this->assertEquals('Test', $data->title);
    }

    public function testToArray(): void
    {
        $item = new Item('test', 'Test', 'Test item');
        $arr = $item->ToArray();
        $this->assertIsArray($arr);
        $this->assertEquals('test', $arr['name']);
        $this->assertEquals('Test', $arr['title']);
    }

    public function testMerge(): void
    {
        $parent = new Item('parent', 'Parent', 'Parent');
        $child1 = new Item('child1', 'Child 1', 'First');
        $child2 = new Item('child2', 'Child 2', 'Second');
        $parent->Merge([$child1, $child2]);
        $this->assertCount(2, $parent->children);
    }
}
