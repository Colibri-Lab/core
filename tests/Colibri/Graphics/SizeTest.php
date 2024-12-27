<?php

use PHPUnit\Framework\TestCase;
use Colibri\Graphics\Size;

class SizeTest extends TestCase
{
    public function testConstructor()
    {
        $size = new Size(100, 200);
        $this->assertEquals(100, $size->width);
        $this->assertEquals(200, $size->height);
    }

    public function testMagicGet()
    {
        $size = new Size(100, 200);
        $this->assertEquals("width:100px;height:200px;", $size->style);
        $this->assertEquals(' width="100" height="200"', $size->attributes);
        $this->assertEquals('&w=100&h=200', $size->params);
        $this->assertFalse($size->isNull);
    }

    public function testTransformTo()
    {
        $size = new Size(100, 200);
        $newSize = $size->TransformTo(new Size(50, 100));
        $this->assertEquals(50, $newSize->width);
        $this->assertEquals(100, $newSize->height);
    }

    public function testTransformToFill()
    {
        $size = new Size(100, 200);
        $newSize = $size->TransformToFill(new Size(50, 100));
        $this->assertEquals(50, $newSize->width);
        $this->assertEquals(100, $newSize->height);
    }

    public function testExpand()
    {
        $size = new Size(100, 200);
        $size->Expand(50, 50);
        $this->assertEquals(150, $size->width);
        $this->assertEquals(250, $size->height);
    }
}
