<?php

use PHPUnit\Framework\TestCase;
use Colibri\IO\FileSystem\Node;
use Colibri\IO\FileSystem\Attributes;

class NodeTest extends TestCase
{
    public function testSetProperty()
    {
        $node = $this->getMockForAbstractClass(Node::class);
        $attributes = $this->createMock(Attributes::class);
        $attributes->expects($this->once())->method('__set')->with('created', '2023-01-01');

        $reflection = new ReflectionClass($node);
        $property = $reflection->getProperty('attributes');
        $property->setAccessible(true);
        $property->setValue($node, $attributes);

        $node->created = '2023-01-01';
    }

    public function testLink()
    {
        $sourcePath = '/path/to/source';
        $destPath = '/path/to/dest';
        Node::Link($sourcePath, $destPath, true, '777');

        $this->assertTrue(is_link($destPath));
    }
}
