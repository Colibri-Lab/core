<?php

use PHPUnit\Framework\TestCase;
use Colibri\Graphics\Point;

class PointTest extends TestCase
{
    public function testConstructor()
    {
        $point = new Point(10, 20);
        $this->assertEquals(10, $point->x);
        $this->assertEquals(20, $point->y);
    }
}
