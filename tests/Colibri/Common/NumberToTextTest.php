<?php

use PHPUnit\Framework\TestCase;
use Colibri\Common\NumberToText;

class NumberToTextTest extends TestCase
{
    public function testConvertRussian(): void
    {
        $result = NumberToText::convert(1, 'ru');
        $this->assertIsString($result);
        $this->assertStringContainsString('рубль', $result);
    }

    public function testConvertRussianZero(): void
    {
        $result = NumberToText::convert(0, 'ru');
        $this->assertIsString($result);
        $this->assertStringContainsString('ноль', $result);
    }

    public function testConvertEnglish(): void
    {
        $result = NumberToText::convert(1, 'en');
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testConvertArmenian(): void
    {
        $result = NumberToText::convert(1, 'hy');
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testConvertItalian(): void
    {
        $result = NumberToText::convert(1, 'it');
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testConvertSpanish(): void
    {
        $result = NumberToText::convert(1, 'es');
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testConvertKazakh(): void
    {
        $result = NumberToText::convert(1, 'kk');
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testConvertUzbek(): void
    {
        $result = NumberToText::convert(1, 'uz');
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testConvertCzech(): void
    {
        $result = NumberToText::convert(1, 'cz');
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testConvertGerman(): void
    {
        $result = NumberToText::convert(1, 'de');
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testConvertPersian(): void
    {
        $result = NumberToText::convert(1, 'fa');
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testConvertChinese(): void
    {
        $result = NumberToText::convert(1, 'zh');
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testConvertTurkish(): void
    {
        $result = NumberToText::convert(1, 'tr');
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testConvertUnsupportedLanguageThrowsException(): void
    {
        $this->expectException(\Exception::class);
        NumberToText::convert(1, 'xx');
    }

    public function testRuMethod(): void
    {
        $result = NumberToText::ru(100);
        $this->assertIsString($result);
        $this->assertStringContainsString('сто', $result);
    }

    public function testEnMethod(): void
    {
        $result = NumberToText::en(100);
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testConvertLargeNumber(): void
    {
        $result = NumberToText::convert(1000000, 'ru');
        $this->assertIsString($result);
        $this->assertStringContainsString('миллион', $result);
    }
}
