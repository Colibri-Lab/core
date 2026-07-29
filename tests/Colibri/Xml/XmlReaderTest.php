<?php

use PHPUnit\Framework\TestCase;
use Colibri\Xml\XmlReader;

class XmlReaderTest extends TestCase
{
    private string $xmlFile;

    protected function setUp(): void
    {
        $this->xmlFile = sys_get_temp_dir() . '/test_colibri_' . uniqid() . '.xml';
        file_put_contents($this->xmlFile, '<?xml version="1.0"?><root><item id="1" name="foo"/><item id="2" name="bar"/></root>');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->xmlFile)) {
            unlink($this->xmlFile);
        }
    }

    public function testEachReadsAllElements(): void
    {
        $reader = new XmlReader($this->xmlFile);
        $elements = [];
        $reader->Each(function ($r, $name, $depth, $attrs) use (&$elements) {
            $elements[] = ['name' => $name, 'depth' => $depth, 'attrs' => $attrs];
        });
        $this->assertCount(3, $elements); // root, item, item
    }

    public function testEachReceivesCorrectNames(): void
    {
        $reader = new XmlReader($this->xmlFile);
        $names = [];
        $reader->Each(function ($r, $name) use (&$names) {
            $names[] = $name;
        });
        $this->assertContains('root', $names);
        $this->assertContains('item', $names);
    }

    public function testEachReceivesAttributes(): void
    {
        $reader = new XmlReader($this->xmlFile);
        $allAttrs = [];
        $reader->Each(function ($r, $name, $depth, $attrs) use (&$allAttrs) {
            if ($name === 'item') {
                $allAttrs[] = $attrs;
            }
        });
        $this->assertCount(2, $allAttrs);
        $this->assertEquals('1', $allAttrs[0]['id']);
        $this->assertEquals('foo', $allAttrs[0]['name']);
    }

    public function testEachReceivesDepth(): void
    {
        $reader = new XmlReader($this->xmlFile);
        $rootDepth = null;
        $itemDepth = null;
        $reader->Each(function ($r, $name, $depth) use (&$rootDepth, &$itemDepth) {
            if ($name === 'root') {
                $rootDepth = $depth;
            } elseif ($name === 'item' && $itemDepth === null) {
                $itemDepth = $depth;
            }
        });
        $this->assertEquals(0, $rootDepth);
        $this->assertEquals(1, $itemDepth);
    }
}
