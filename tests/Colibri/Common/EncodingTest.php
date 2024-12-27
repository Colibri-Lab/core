<?php

use PHPUnit\Framework\TestCase;
use Colibri\Common\Encoding;

class EncodingTest extends TestCase
{
    public function testConvert()
    {
        $string = 'халлоу';
        $converted = Encoding::Convert($string, Encoding::CP1251);
        $this->assertNotEquals($string, $converted);
    }

    public function testCheck()
    {
        $string = 'халлоу';
        $this->assertTrue(Encoding::Check($string, Encoding::UTF8));
        $this->assertFalse(Encoding::Check($string, Encoding::CP1251));
    }

    public function testDetect()
    {
        $string = 'hello';
        $this->assertEquals(Encoding::UTF8, Encoding::Detect($string));
    }
}
