<?php

use PHPUnit\Framework\TestCase;
use Colibri\Common\Javascript;

class JavascriptTest extends TestCase
{
    public function testShrink()
    {
        $input = 'function test() { return 1; }';
        $expected = 'function test(){return 1;}';
        $this->assertEquals($expected, Javascript::Shrink($input));
    }
}
