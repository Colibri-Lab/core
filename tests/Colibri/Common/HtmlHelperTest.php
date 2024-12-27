<?php

use PHPUnit\Framework\TestCase;
use Colibri\Common\HtmlHelper;

class HtmlHelperTest extends TestCase
{
    public function testEncode()
    {
        $input = ['key' => 'value'];
        $expected = '<div class="object"><div class="key">value</div></div>';
        $this->assertEquals($expected, HtmlHelper::Encode($input));
    }

    public function testDecode()
    {
        $input = '<div class="object"><div class="key">value</div></div>';
        $xmlNode = HtmlHelper::Decode($input);
        $this->assertEquals('value', $xmlNode->key);
    }
}
