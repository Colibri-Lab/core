<?php

use PHPUnit\Framework\TestCase;
use Colibri\Graphics\Graphics;
use Colibri\Graphics\Size;
use Colibri\Graphics\Point;

class GraphicsTest extends TestCase
{
    public function testLoadFromData()
    {
        $graphics = new Graphics();
        $data = file_get_contents(__DIR__ . '/test.png');
        $graphics->LoadFromData($data);
        $this->assertTrue($graphics->isValid);
    }

    public function testLoadFromFile()
    {
        $graphics = new Graphics();
        $graphics->LoadFromFile(__DIR__ . '/test.png');
        $this->assertTrue($graphics->isValid);
    }

    public function testLoadEmptyImage()
    {
        $graphics = new Graphics();
        $graphics->LoadEmptyImage(new Size(100, 100));
        $this->assertTrue($graphics->isValid);
    }

    public function testResize()
    {
        $graphics = new Graphics();
        $graphics->LoadEmptyImage(new Size(100, 100));
        $graphics->Resize(new Size(50, 50));
        $this->assertEquals(50, $graphics->size->width);
        $this->assertEquals(50, $graphics->size->height);
    }

    public function testRotate()
    {
        $graphics = new Graphics();
        $graphics->LoadEmptyImage(new Size(100, 100));
        $graphics->Rotate(90);
        $this->assertTrue($graphics->isValid);
    }

    public function testCrop()
    {
        $graphics = new Graphics();
        $graphics->LoadEmptyImage(new Size(100, 100));
        $graphics->Crop(new Size(50, 50), new Point(10, 10));
        $this->assertEquals(50, $graphics->size->width);
        $this->assertEquals(50, $graphics->size->height);
    }

    public function testApplyFilter()
    {
        $graphics = new Graphics();
        $graphics->LoadEmptyImage(new Size(100, 100));
        $result = $graphics->ApplyFilter(IMG_FILTER_GRAYSCALE);
        $this->assertTrue($result);
    }

    public function testSave()
    {
        $graphics = new Graphics();
        $graphics->LoadEmptyImage(new Size(100, 100));
        $graphics->Save(__DIR__ . '/output.png');
        $this->assertFileExists(__DIR__ . '/output.png');
    }
}
