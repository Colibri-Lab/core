<?php

use PHPUnit\Framework\TestCase;
use Colibri\Utils\Debug;

class DebugTest extends TestCase
{
    public function testOut()
    {
        $this->expectOutputString("test\n");
        Debug::Out('test');
    }

    public function testROut()
    {
        $result = Debug::ROut('test');
        $this->assertStringContainsString('test', $result);
    }

    public function testIOut()
    {
        $this->expectOutputString("<pre>\nArray\n(\n    [0] => test\n)\n</pre>");
        Debug::IOut('test');
    }
}
