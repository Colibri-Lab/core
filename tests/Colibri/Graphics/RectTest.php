<?php

use PHPUnit\Framework\TestCase;
use Colibri\Graphics\Rect;
use Colibri\Graphics\Point;

class RectTest extends TestCase
{
    public function testConstructor()
    {
        $rect = new Rect();
        $this->assertNull($rect->lowerleft);
        $this->assertNull($rect->lowerright);
        $this->assertNull($rect->upperleft);
        $this->assertNull($rect->upperright);
    }
}
