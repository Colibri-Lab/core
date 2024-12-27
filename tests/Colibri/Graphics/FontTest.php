<?php

use PHPUnit\Framework\TestCase;
use Colibri\Graphics\Font;
use Colibri\Graphics\Point;
use Colibri\Graphics\Size;

class FontTest extends TestCase
{
    public function testConstructor()
    {
        $font = new Font('Arial', '/path/to/fonts', 12, 0);
        $this->assertEquals('Arial', $font->file);
        $this->assertEquals('/path/to/fonts', $font->path);
        $this->assertEquals(12, $font->size);
        $this->assertEquals(0, $font->angle);
    }

    public function testMeasureText()
    {
        $font = new Font('Arial', '/path/to/fonts', 12, 0);
        $rect = $font->MeasureText('Hello');
        $this->assertInstanceOf(Rect::class, $rect);
    }

    public function testInscribeText()
    {
        $font = new Font('Arial', '/path/to/fonts', 12, 0);
        $startAt = new Point(0, 0);
        $size = new Size(0, 0);
        $font->InscribeText('Hello', $startAt, $size);
        $this->assertGreaterThan(0, $size->width);
        $this->assertGreaterThan(0, $size->height);
    }
}
