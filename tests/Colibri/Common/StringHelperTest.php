<?php

use PHPUnit\Framework\TestCase;
use Colibri\Common\StringHelper;

class StringHelperTest extends TestCase
{
    public function testToLower()
    {
        $this->assertEquals('test', StringHelper::ToLower('TEST'));
    }

    public function testToUpper()
    {
        $this->assertEquals('TEST', StringHelper::ToUpper('test'));
    }

    public function testIsUpper()
    {
        $this->assertTrue(StringHelper::IsUpper('TEST'));
        $this->assertFalse(StringHelper::IsUpper('test'));
    }

    public function testIsLower()
    {
        $this->assertTrue(StringHelper::IsLower('test'));
        $this->assertFalse(StringHelper::IsLower('TEST'));
    }

    public function testIsJsonString()
    {
        $this->assertTrue(StringHelper::IsJsonString('{"key": "value"}'));
        $this->assertFalse(StringHelper::IsJsonString('not a json string'));
    }

    public function testToUpperFirst()
    {
        $this->assertEquals('Test', StringHelper::ToUpperFirst('test'));
    }

    public function testReplace()
    {
        $this->assertEquals('hello world', StringHelper::Replace('hello', 'hello', 'hello world'));
    }

    public function testToCamelCaseAttr()
    {
        $this->assertEquals('helloWorld', StringHelper::ToCamelCaseAttr('hello-world'));
    }

    public function testFromCamelCaseAttr()
    {
        $this->assertEquals('hello-world', StringHelper::FromCamelCaseAttr('helloWorld'));
    }

    public function testToCamelCaseVar()
    {
        $this->assertEquals('helloWorld', StringHelper::ToCamelCaseVar('hello_world'));
    }

    public function testFromCamelCaseVar()
    {
        $this->assertEquals('hello_world', StringHelper::FromCamelCaseVar('helloWorld'));
    }

    public function testIsEmail()
    {
        $this->assertTrue(StringHelper::IsEmail('test@example.com'));
        $this->assertFalse(StringHelper::IsEmail('not an email'));
    }

    public function testIsUrl()
    {
        $this->assertTrue(StringHelper::IsUrl('http://example.com'));
        $this->assertFalse(StringHelper::IsUrl('not a url'));
    }

    public function testEndsWith()
    {
        $this->assertTrue(StringHelper::EndsWith('hello world', 'world'));
        $this->assertFalse(StringHelper::EndsWith('hello world', 'hello'));
    }

    public function testStartsWith()
    {
        $this->assertTrue(StringHelper::StartsWith('hello world', 'hello'));
        $this->assertFalse(StringHelper::StartsWith('hello world', 'world'));
    }

    public function testUrlToNamespace()
    {
        $this->assertEquals('Hello\\World', StringHelper::UrlToNamespace('hello/world'));
    }

    public function testAddToQueryString()
    {
        $this->assertEquals('http://example.com?key=value', StringHelper::AddToQueryString('http://example.com', ['key' => 'value']));
    }

    public function testRandomize()
    {
        $this->assertEquals(10, strlen(StringHelper::Randomize(10)));
    }

    public function testPrepareAttribute()
    {
        $this->assertEquals('test', StringHelper::PrepareAttribute('test'));
    }

    public function testUnescape()
    {
        $this->assertEquals('test', StringHelper::Unescape('test'));
    }

    public function testStripHTML()
    {
        $this->assertEquals('test', StringHelper::StripHTML('<p>test</p>'));
    }

    public function testSubstring()
    {
        $this->assertEquals('test', StringHelper::Substring('test string', 0, 4));
    }

    public function testLength()
    {
        $this->assertEquals(11, StringHelper::Length('test string'));
    }

    public function testFormatSequence()
    {
        $this->assertEquals('1 год', StringHelper::FormatSequence(1));
    }

    public function testFormatFileSize()
    {
        $this->assertEquals('1 Kb', StringHelper::FormatFileSize(1024));
    }

    public function testTrimLength()
    {
        $this->assertEquals('test...', StringHelper::TrimLength('test string', 7));
    }

    public function testWords()
    {
        $this->assertEquals('test...', StringHelper::Words('test string', 1));
    }

    public function testUniqueWords()
    {
        $this->assertEquals(['test'], StringHelper::UniqueWords('test test'));
    }

    public function testExpand()
    {
        $this->assertEquals('000test', StringHelper::Expand('test', 7, '0'));
    }

    public function testMd5ToGUID()
    {
        $this->assertEquals('d41d8cd9-8f00-3204-a980-0998ecf8427e', StringHelper::Md5ToGUID(md5('')));
    }

    public function testGUID()
    {
        $this->assertEquals(36, strlen(StringHelper::GUID()));
    }

    public function testExplode()
    {
        $this->assertEquals(['test', 'string'], StringHelper::Explode('test string', ' '));
    }

    public function testImplode()
    {
        $this->assertEquals('test string', StringHelper::Implode(['test', 'string'], ' '));
    }

    public function testImplodeWithKeys()
    {
        $this->assertEquals('key=value', StringHelper::ImplodeWithKeys(['key' => 'value'], '&', '='));
    }

    public function testParseAsUrl()
    {
        $url = StringHelper::ParseAsUrl('http://example.com?key=value');
        $this->assertEquals('example.com', $url->host);
    }

    public function testTransliterate()
    {
        $this->assertEquals('privet', StringHelper::Transliterate('привет'));
    }

    public function testTransliterateBack()
    {
        $this->assertEquals('привет', StringHelper::TransliterateBack('privet'));
    }

    public function testCreateHID()
    {
        $this->assertEquals('privet', StringHelper::CreateHID('привет'));
    }

    public function testAddNoIndex()
    {
        $this->assertEquals('<!--noindex--><a rel="nofollow" href="#">link</a><!--/noindex-->', StringHelper::AddNoIndex('<a href="#">link</a>'));
    }

    public function testStripHtmlAndBody()
    {
        $this->assertEquals('content', StringHelper::StripHtmlAndBody('<html><body>content</body></html>'));
    }

    public function testRemoveEmoji()
    {
        $this->assertEquals('test', StringHelper::RemoveEmoji('test😊'));
    }

    public function testToObject()
    {
        $object = StringHelper::ToObject('key=value');
        $this->assertEquals('value', $object->key);
    }

    public function testTrim()
    {
        $this->assertEquals('test', StringHelper::Trim(' test '));
    }

    public function testReplaceInObject()
    {
        $object = (object)['key' => 'value'];
        $replaced = StringHelper::ReplaceInObject($object, 'value', 'new value');
        $this->assertEquals('new value', $replaced->key);
    }

    public function testClearPhone()
    {
        $this->assertEquals('1234567890', StringHelper::ClearPhone('+1 (234) 567-890'));
    }
}
