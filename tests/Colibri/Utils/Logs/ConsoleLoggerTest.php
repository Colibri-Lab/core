<?php

use PHPUnit\Framework\TestCase;
use Colibri\Utils\Logs\ConsoleLogger;

class ConsoleLoggerTest extends TestCase
{
    public function testWriteLine()
    {
        $logger = new ConsoleLogger();
        $this->expectOutputRegex('/Test message/');
        $logger->WriteLine(1, 'Test message');
    }

    public function testContent()
    {
        $logger = new ConsoleLogger();
        $content = $logger->Content();
        $this->assertEmpty($content);
    }
}
