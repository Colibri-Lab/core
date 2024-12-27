<?php

use PHPUnit\Framework\TestCase;
use Colibri\Common\NoLangHelper;

class NoLangHelperTest extends TestCase
{
    public function testParseString()
    {
        $this->assertEquals('default', NoLangHelper::ParseString('#{lang;default}'));
        $this->assertEquals('default', NoLangHelper::ParseString('#{lang;default} text'));
    }

    public function testParseArray()
    {
        $input = ['key' => '#{lang;default}'];
        $expected = ['key' => 'default'];
        $this->assertEquals($expected, NoLangHelper::ParseArray($input));
    }
}
