<?php

use PHPUnit\Framework\TestCase;
use Colibri\Utils\Logs\MemoryLogger;

class MemoryLoggerTest extends TestCase
{
    public function testWriteLine()
    {
        $logger = new MemoryLogger();
        $logger->WriteLine(1, 'Test message');
        $content = $logger->Content();
        $this->assertNotEmpty($content);
        $this->assertStringContainsString('Test message', $content[0]);
    }

    public function testContent()
    {
        $logger = new MemoryLogger();
        $logger->WriteLine(1, 'Test message');
        $content = $logger->Content();
        $this->assertIsArray($content);
        $this->assertCount(1, $content);
    }
}
