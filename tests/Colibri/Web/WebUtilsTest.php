<?php

use PHPUnit\Framework\TestCase;
use Colibri\Web\WebUtils;

class WebUtilsTest extends TestCase
{
    public function testConstantsAreDefined(): void
    {
        $this->assertEquals('json', WebUtils::JSON);
        $this->assertEquals('xml', WebUtils::XML);
        $this->assertEquals('html', WebUtils::HTML);
        $this->assertEquals('txt', WebUtils::Text);
        $this->assertEquals('css', WebUtils::CSS);
        $this->assertEquals('js', WebUtils::JS);
        $this->assertEquals('stream', WebUtils::Stream);
    }

    public function testErrorConstantsDefined(): void
    {
        $this->assertEquals(1, WebUtils::IncorrectCommandObject);
        $this->assertEquals(2, WebUtils::UnknownMethodInObject);
    }

    public function testGetControllerFullNameSimpleClass(): void
    {
        $result = WebUtils::GetControllerFullName('/home/');
        $this->assertStringContainsString('Controller', $result);
    }

    public function testGetControllerFullNameModuleClass(): void
    {
        $result = WebUtils::GetControllerFullName('/modules/mymodule/');
        $this->assertStringContainsString('Controllers', $result);
        $this->assertStringContainsString('Controller', $result);
    }

    public function testParseCommandWithExtension(): void
    {
        [$type, $class, $method, $typed] = WebUtils::ParseCommand('/path/to/index.html');
        $this->assertEquals('html', $type);
        $this->assertStringContainsString('Controller', $class);
        $this->assertTrue($typed);
    }

    public function testParseCommandJsonType(): void
    {
        [$type, $class, $method, $typed] = WebUtils::ParseCommand('/path/to/action.json');
        $this->assertEquals('json', $type);
    }

    public function testConvertDataToCharsetString(): void
    {
        $result = WebUtils::ConvertDataToCharset('hello', 'UTF-8');
        $this->assertEquals('hello', $result);
    }

    public function testConvertDataToCharsetArray(): void
    {
        $result = WebUtils::ConvertDataToCharset(['key' => 'value'], 'UTF-8');
        $this->assertIsArray($result);
        $this->assertEquals('value', $result['key']);
    }
}
